<?php

namespace App\Services\Billing;

use App\Models\Central\TenantMembership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PlatformSubscriptionMetricsService
{
    public function __construct(
        private readonly FxRateService $fx,
    ) {}

    /**
     * @return array{
     *   by_currency: array<string, array{mrr: float, arr: float, active_paid: int}>,
     *   primary_currency: string,
     *   base_currency: string,
     *   mrr: float,
     *   arr: float,
     *   mrr_base: float,
     *   arr_base: float,
     *   active_paid: int,
     *   churned_30d: int,
     *   churn_rate_30d: float
     * }
     */
    public function snapshot(?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $base = $this->fx->baseCurrency();

        if (! Schema::connection('central')->hasTable('tenant_memberships')) {
            return $this->emptySnapshot($base);
        }

        $active = TenantMembership::query()
            ->where('status', 'active')
            ->where('amount', '>', 0)
            ->get(['id', 'amount', 'billing_cycle', 'currency']);

        $byCurrency = [];
        $mrrBase = 0.0;

        foreach ($active as $membership) {
            $currency = strtoupper((string) ($membership->currency ?: 'USD'));
            $mrr = $this->monthlyEquivalent((float) $membership->amount, (string) $membership->billing_cycle);
            if (! isset($byCurrency[$currency])) {
                $byCurrency[$currency] = ['mrr' => 0.0, 'arr' => 0.0, 'active_paid' => 0];
            }
            $byCurrency[$currency]['mrr'] += $mrr;
            $byCurrency[$currency]['active_paid']++;

            try {
                $mrrBase += $this->fx->convert($mrr, $currency, $base, $asOf);
            } catch (\Throwable) {
                // Skip unconvertible rows rather than failing the dashboard.
            }
        }

        foreach ($byCurrency as $currency => $row) {
            $byCurrency[$currency]['mrr'] = BillingMoney::round($row['mrr'], $currency);
            $byCurrency[$currency]['arr'] = BillingMoney::round($row['mrr'] * 12, $currency);
        }

        $mrrBase = BillingMoney::round($mrrBase, $base);
        $arrBase = BillingMoney::round($mrrBase * 12, $base);

        $primary = $base;
        $primaryRow = $byCurrency[$primary] ?? null;

        $windowStart = $asOf->copy()->subDays(30);
        $churned = $this->churnedInWindow($windowStart, $asOf);
        $activePaid = (int) collect($byCurrency)->sum('active_paid');
        $activeAtStart = $activePaid + $churned;
        $churnRate = $activeAtStart > 0 ? round(($churned / $activeAtStart) * 100, 1) : 0.0;

        return [
            'by_currency'      => $byCurrency,
            'primary_currency' => $primary,
            'base_currency'    => $base,
            // Headline figures are FX-normalized into base currency.
            'mrr'              => $mrrBase,
            'arr'              => $arrBase,
            'mrr_base'         => $mrrBase,
            'arr_base'         => $arrBase,
            'active_paid'      => $activePaid,
            'churned_30d'      => $churned,
            'churn_rate_30d'   => $churnRate,
            'native_primary_mrr' => $primaryRow['mrr'] ?? 0.0,
        ];
    }

    public function monthlyEquivalent(float $amount, string $billingCycle): float
    {
        return strtolower($billingCycle) === 'yearly'
            ? round($amount / 12, 4)
            : $amount;
    }

    private function churnedInWindow(Carbon $from, Carbon $to): int
    {
        return TenantMembership::query()
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($inner) use ($from, $to) {
                    $inner->where('status', 'cancelled')
                        ->whereBetween('cancelled_at', [$from, $to]);
                })->orWhere(function ($inner) use ($from, $to) {
                    $inner->where('status', 'expired')
                        ->whereBetween('end_date', [$from->toDateString(), $to->toDateString()]);
                });
            })
            ->count();
    }

    /** @return array<string, mixed> */
    private function emptySnapshot(string $base = 'USD'): array
    {
        return [
            'by_currency'        => [],
            'primary_currency'   => $base,
            'base_currency'      => $base,
            'mrr'                => 0.0,
            'arr'                => 0.0,
            'mrr_base'           => 0.0,
            'arr_base'           => 0.0,
            'active_paid'        => 0,
            'churned_30d'        => 0,
            'churn_rate_30d'     => 0.0,
            'native_primary_mrr' => 0.0,
        ];
    }
}
