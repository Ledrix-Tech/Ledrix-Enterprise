<?php

namespace App\Services\Tenant;

use App\Exceptions\ImportPlanLimitException;
use App\Models\Central\Tenant;
use App\Models\ImportBatch;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

class HistoricalImportLimitService
{
    public function __construct(
        private SubscriptionAccessService $subscriptions,
    ) {}

    /** @return array{start: Carbon, end: Carbon} */
    public function billingCycleWindow(Tenant $tenant): array
    {
        $membership = $this->subscriptions->currentMembership($tenant);

        if ($membership?->start_date) {
            return [
                'start' => $membership->start_date->copy()->startOfDay(),
                'end'   => ($membership->end_date ?? now())->copy()->endOfDay(),
            ];
        }

        if ($tenant->trial_ends_at) {
            $trialDays = (int) ($tenant->plan?->trial_days ?? 14);

            return [
                'start' => $tenant->trial_ends_at->copy()->subDays(max(1, $trialDays))->startOfDay(),
                'end'   => $tenant->trial_ends_at->copy()->endOfDay(),
            ];
        }

        return [
            'start' => now()->startOfMonth(),
            'end'   => now()->endOfMonth(),
        ];
    }

    public function uploadsThisCycle(int $tenantId): int
    {
        if (! Schema::hasTable('import_batches')) {
            return 0;
        }

        $tenant = $this->resolveTenant($tenantId);
        if (! $tenant) {
            return ImportBatch::query()->where('tenant_id', $tenantId)->count();
        }

        $window = $this->billingCycleWindow($tenant);

        return ImportBatch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $window['start'])
            ->where('created_at', '<=', $window['end'])
            ->count();
    }

    public function resetDate(Tenant $tenant): Carbon
    {
        return $this->billingCycleWindow($tenant)['end'];
    }

    /**
     * Inspect a CSV without creating a batch. Does not change import cascade logic.
     *
     * @return array{rows: int, headers: list<string>, distinct_brands: list<string>, implies_multi_brand: bool}
     */
    public function inspectCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['rows' => 0, 'headers' => [], 'distinct_brands' => [], 'implies_multi_brand' => false];
        }

        $rawHeaders = fgetcsv($handle);
        $headers = [];
        if (is_array($rawHeaders) && $rawHeaders !== [null]) {
            foreach ($rawHeaders as $i => $header) {
                $header = (string) $header;
                if ($i === 0) {
                    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
                }
                $headers[] = trim($header);
            }
        }

        $brandIndex = $this->brandColumnIndex($headers);
        $brands = [];
        $rows = 0;

        while (($cols = fgetcsv($handle)) !== false) {
            if ($cols === [null] || $this->rowIsEmpty($cols)) {
                continue;
            }
            $rows++;
            if ($brandIndex !== null) {
                $value = trim((string) ($cols[$brandIndex] ?? ''));
                if ($value !== '') {
                    $brands[strtolower($value)] = $value;
                }
            }
        }

        fclose($handle);

        $distinct = array_values($brands);

        return [
            'rows'                => $rows,
            'headers'             => $headers,
            'distinct_brands'     => $distinct,
            'implies_multi_brand' => $brandIndex !== null && count($distinct) > 1,
        ];
    }

    public function assertCanUpload(?int $tenantId, UploadedFile $file, bool $multiBrandRequested): void
    {
        $tenant = $this->resolveTenant($tenantId);
        if (! $tenant || ! $this->planHasImportColumns()) {
            return;
        }

        $inspected = $this->inspectCsv($file->getRealPath() ?: $file->getPathname());
        $this->assertWithinPlan($tenant, $inspected, $multiBrandRequested);
    }

    /**
     * @param  array<string, string>  $mapping
     */
    public function assertCanMap(?int $tenantId, bool $multiBrandRequested, array $mapping = []): void
    {
        $tenant = $this->resolveTenant($tenantId);
        if (! $tenant || ! $this->planHasImportColumns()) {
            return;
        }

        if (($multiBrandRequested || in_array('brand_name', $mapping, true)) && ! $this->allowsMultiBrand($tenant)) {
            $this->throwMultiBrand($tenant);
        }
    }

    /**
     * @param  array{rows: int, headers: list<string>, distinct_brands: list<string>, implies_multi_brand: bool}  $inspected
     */
    public function assertWithinPlan(Tenant $tenant, array $inspected, bool $multiBrandRequested): void
    {
        $upgradeUrl = $this->upgradeUrl();

        if (($multiBrandRequested || $inspected['implies_multi_brand']) && ! $this->allowsMultiBrand($tenant)) {
            $this->throwMultiBrand($tenant);
        }

        $maxRows = $this->maxRowsPerUpload($tenant);
        if ($maxRows !== -1 && $inspected['rows'] > $maxRows) {
            $planName = $tenant->plan?->name ?? 'your plan';
            $next = $this->nextTierCopy($tenant);
            throw new ImportPlanLimitException(
                headline: 'Your file has '.$inspected['rows'].' rows — '.$planName.' supports up to '.$maxRows.' per import.',
                workaround: 'Split your file into smaller batches of '.$maxRows.' rows or fewer.',
                upgrade: 'Or upgrade to '.$next['name'].' for '.$next['rows'].' per import.',
                upgradeUrl: $upgradeUrl,
            );
        }

        $used = $this->uploadsThisCycle((int) $tenant->id);
        $maxUploads = $this->maxUploadsPerCycle($tenant);
        $reset = $this->resetDate($tenant)->format('M j, Y');

        if (! $this->allowsReimport($tenant) && $used >= 1) {
            throw new ImportPlanLimitException(
                headline: 'You’ve used your 1 import for this billing cycle. Resets on '.$reset.'.',
                workaround: 'Wait until the cycle resets, or split remaining work across the next cycle.',
                upgrade: 'Upgrade to '.$this->nextTierCopy($tenant)['name'].' for '.$this->nextTierCopy($tenant)['uploads'].'.',
                upgradeUrl: $upgradeUrl,
            );
        }

        if ($maxUploads !== -1 && $used >= $maxUploads) {
            $label = $maxUploads === 1 ? '1 import' : $maxUploads.' imports';
            throw new ImportPlanLimitException(
                headline: 'You’ve used your '.$label.' for this billing cycle. Resets on '.$reset.'.',
                workaround: 'Wait until '.$reset.' to upload again, or export leftover rows into the next cycle.',
                upgrade: 'Upgrade to '.$this->nextTierCopy($tenant)['name'].' for '.$this->nextTierCopy($tenant)['uploads'].'.',
                upgradeUrl: $upgradeUrl,
            );
        }
    }

    public function maxRowsPerUpload(Tenant $tenant): int
    {
        return $this->effectiveInt($tenant, 'import_max_rows_per_upload', 150);
    }

    public function maxUploadsPerCycle(Tenant $tenant): int
    {
        return $this->effectiveInt($tenant, 'import_max_uploads_per_month', 1);
    }

    public function allowsMultiBrand(Tenant $tenant): bool
    {
        return $this->effectiveFlag($tenant, 'import_multi_brand_allowed');
    }

    public function allowsReimport(Tenant $tenant): bool
    {
        return $this->effectiveFlag($tenant, 'import_reimport_allowed');
    }

    public function currentPlanId(?int $tenantId = null): ?int
    {
        $tenant = $this->resolveTenant($tenantId);

        return $tenant?->plan_id ? (int) $tenant->plan_id : null;
    }

    /** @return array{uploads_used: int, uploads_max: int|null, rows_max: int|null, multi_brand: bool, reset_on: ?string} */
    public function usageForUi(?int $tenantId = null): array
    {
        $tenant = $this->resolveTenant($tenantId);
        if (! $tenant || ! $this->planHasImportColumns()) {
            return [
                'uploads_used' => 0,
                'uploads_max'  => null,
                'rows_max'     => null,
                'multi_brand'  => true,
                'reset_on'     => null,
            ];
        }

        $maxUploads = $this->maxUploadsPerCycle($tenant);
        $maxRows = $this->maxRowsPerUpload($tenant);

        return [
            'uploads_used' => $this->uploadsThisCycle((int) $tenant->id),
            'uploads_max'  => $maxUploads === -1 ? null : $maxUploads,
            'rows_max'     => $maxRows === -1 ? null : $maxRows,
            'multi_brand'  => $this->allowsMultiBrand($tenant),
            'reset_on'     => $this->resetDate($tenant)->format('M j, Y'),
        ];
    }

    private function throwMultiBrand(Tenant $tenant): void
    {
        $planName = $tenant->plan?->name ?? 'your plan';
        $next = $this->nextTierCopy($tenant);

        throw new ImportPlanLimitException(
            headline: $planName.' allows a single brand per import.',
            workaround: 'Choose one brand in the dropdown and remove extra brand columns, or split the sheet by brand.',
            upgrade: 'Upgrade to '.$next['name'].' for multi-brand sheets.',
            upgradeUrl: $this->upgradeUrl(),
        );
    }

    /** @return array{name: string, rows: string, uploads: string} */
    private function nextTierCopy(Tenant $tenant): array
    {
        $slug = strtolower((string) ($tenant->plan?->slug ?? ''));

        if (str_contains($slug, 'standard') || str_contains($slug, 'growth')) {
            return [
                'name'    => 'Premium',
                'rows'    => 'unlimited rows',
                'uploads' => 'unlimited imports per month',
            ];
        }

        return [
            'name'    => 'Standard',
            'rows'    => 'up to 1,000 rows',
            'uploads' => '5 imports per month',
        ];
    }

    private function upgradeUrl(): string
    {
        return route('admin.org.plan');
    }

    private function effectiveInt(Tenant $tenant, string $key, int $fallback): int
    {
        $override = $this->limitOverride($tenant);
        if ($override && ! $override->isExpired() && $override->{$key} !== null) {
            return (int) $override->{$key};
        }

        if ($tenant->plan && isset($tenant->plan->{$key})) {
            return (int) $tenant->plan->{$key};
        }

        return $fallback;
    }

    private function effectiveFlag(Tenant $tenant, string $key): bool
    {
        $override = $this->limitOverride($tenant);
        if ($override && ! $override->isExpired() && $override->{$key} !== null) {
            return (int) $override->{$key} === 1;
        }

        return (bool) ($tenant->plan?->{$key} ?? false);
    }

    private function resolveTenant(?int $tenantId): ?Tenant
    {
        $tenantId = $tenantId ?? TenantContext::resolve();
        if (! $tenantId) {
            return null;
        }

        try {
            return Tenant::query()->with(['plan', 'activeMembership'])->find($tenantId);
        } catch (\Throwable) {
            return null;
        }
    }

    private function limitOverride(Tenant $tenant): mixed
    {
        try {
            return $tenant->limitOverride;
        } catch (\Throwable) {
            return null;
        }
    }

    private function planHasImportColumns(): bool
    {
        try {
            return Schema::connection('central')->hasColumn('package_pricings', 'import_max_uploads_per_month');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $headers
     */
    private function brandColumnIndex(array $headers): ?int
    {
        foreach ($headers as $i => $header) {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $header) ?? $header);
            $key = trim($key);
            if (in_array($key, ['brand name', 'brand', 'brand id', 'llc'], true)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $cols
     */
    private function rowIsEmpty(array $cols): bool
    {
        foreach ($cols as $col) {
            if (trim((string) $col) !== '') {
                return false;
            }
        }

        return true;
    }
}
