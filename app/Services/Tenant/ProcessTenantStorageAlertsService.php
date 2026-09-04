<?php

namespace App\Services\Tenant;

use App\Mail\TenantStorageAlertMail;
use App\Models\Central\Tenant;
use App\Models\Central\TenantUsageSnapshot;
use App\Support\SafeMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTenantStorageAlertsService
{
    public function __construct(
        private TenantUsageService $usage,
    ) {}

    /**
     * @return array{alerted_80: int, alerted_100: int, cleared: int, skipped: int}
     */
    public function run(): array
    {
        $stats = [
            'alerted_80'  => 0,
            'alerted_100' => 0,
            'cleared'     => 0,
            'skipped'     => 0,
        ];

        $tenants = Tenant::query()
            ->with(['plan', 'limitOverride'])
            ->whereNull('deleted_at')
            ->get();

        foreach ($tenants as $tenant) {
            try {
                $result = $this->processTenant($tenant);
                $stats['alerted_80'] += $result['alerted_80'] ? 1 : 0;
                $stats['alerted_100'] += $result['alerted_100'] ? 1 : 0;
                $stats['cleared'] += $result['cleared'] ? 1 : 0;
                $stats['skipped'] += $result['skipped'] ? 1 : 0;
            } catch (Throwable $e) {
                Log::warning('Storage alert processing failed', [
                    'tenant_id' => $tenant->id,
                    'message'   => $e->getMessage(),
                ]);
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * Pure threshold decision for unit tests.
     *
     * @return array{send_80: bool, send_100: bool, clear: bool, skip: bool}
     */
    public function decide(int $usedMb, int $limitMb, bool $alert80Sent, bool $alert100Sent): array
    {
        // Unlimited (-1) or no positive quota → no alerts.
        if ($limitMb === -1 || $limitMb <= 0) {
            return [
                'send_80'  => false,
                'send_100' => false,
                'clear'    => false,
                'skip'     => true,
            ];
        }

        $percent = ($usedMb / $limitMb) * 100;

        if ($percent < 70) {
            return [
                'send_80'  => false,
                'send_100' => false,
                'clear'    => $alert80Sent || $alert100Sent,
                'skip'     => false,
            ];
        }

        return [
            'send_80'  => $percent >= 80 && ! $alert80Sent,
            'send_100' => $percent >= 100 && ! $alert100Sent,
            'clear'    => false,
            'skip'     => false,
        ];
    }

    /**
     * @return array{alerted_80: bool, alerted_100: bool, cleared: bool, skipped: bool}
     */
    private function processTenant(Tenant $tenant): array
    {
        $limit = $tenant->limit('max_storage_mb');
        $snapshot = $this->usage->syncSnapshot((int) $tenant->id);
        $used = (int) ($snapshot->storage_used_mb ?? 0);

        $decision = $this->decide(
            $used,
            $limit,
            $snapshot->storage_alert_80_sent_at !== null,
            $snapshot->storage_alert_100_sent_at !== null,
        );

        if ($decision['skip']) {
            return ['alerted_80' => false, 'alerted_100' => false, 'cleared' => false, 'skipped' => true];
        }

        if ($decision['clear']) {
            $snapshot->forceFill([
                'storage_alert_80_sent_at'  => null,
                'storage_alert_100_sent_at' => null,
            ])->save();

            return ['alerted_80' => false, 'alerted_100' => false, 'cleared' => true, 'skipped' => false];
        }

        $alerted80 = false;
        $alerted100 = false;

        if ($decision['send_80']) {
            $this->sendAlert($tenant, $used, $limit, 80, $snapshot);
            $alerted80 = true;
        }

        if ($decision['send_100']) {
            $this->sendAlert($tenant, $used, $limit, 100, $snapshot);
            $alerted100 = true;
        }

        return [
            'alerted_80'  => $alerted80,
            'alerted_100' => $alerted100,
            'cleared'     => false,
            'skipped'     => false,
        ];
    }

    private function sendAlert(
        Tenant $tenant,
        int $usedMb,
        int $limitMb,
        int $thresholdPercent,
        TenantUsageSnapshot $snapshot,
    ): void {
        $to = $tenant->billing_email ?: $tenant->email;
        if (! $to) {
            return;
        }

        $sent = SafeMail::send(
            $to,
            new TenantStorageAlertMail($tenant, $usedMb, $limitMb, $thresholdPercent),
            'storage alert',
            ['tenant_id' => $tenant->id, 'threshold' => $thresholdPercent],
        );

        if (! $sent) {
            return;
        }

        $column = $thresholdPercent >= 100
            ? 'storage_alert_100_sent_at'
            : 'storage_alert_80_sent_at';

        $snapshot->forceFill([$column => now()])->save();
    }
}
