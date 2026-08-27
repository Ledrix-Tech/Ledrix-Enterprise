<?php

namespace App\Services\Billing;

use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;

class SubscriptionPricingService
{
    public function __construct(
        private readonly FxRateService $fx,
    ) {}

    public function resolveAmount(
        PackagePricing $plan,
        string $billingCycle,
        string $currency,
    ): float {
        $currency = strtoupper($currency);
        $billingCycle = strtolower($billingCycle) === 'yearly' ? 'yearly' : 'monthly';

        if ($currency === 'PKR') {
            $pkr = $billingCycle === 'yearly'
                ? $plan->yearly_price_pkr
                : $plan->monthly_price_pkr;

            if ($pkr !== null && (float) $pkr > 0) {
                return BillingMoney::round((float) $pkr, 'PKR');
            }

            $usd = $this->usdListPrice($plan, $billingCycle);

            return $this->fx->convert($usd, 'USD', 'PKR');
        }

        if ($currency === 'USD') {
            return BillingMoney::round($this->usdListPrice($plan, $billingCycle), 'USD');
        }

        // AED / EUR / GBP / etc.: convert from USD list price via FX.
        $usd = $this->usdListPrice($plan, $billingCycle);

        return $this->fx->convert($usd, 'USD', $currency);
    }

    public function usdToPkrRate(): float
    {
        return $this->fx->rate('USD', 'PKR');
    }

    /**
     * @return array{pkr: float, usd: float, aed: float, amounts: array<string, float>, cycle: string}
     */
    public function displayAmount(Tenant $tenant, ?TenantMembership $membership = null): array
    {
        $tenant->loadMissing('plan');
        $membership ??= $tenant->activeMembership;

        $cycle = $membership?->billing_cycle ?? 'monthly';
        $plan = $tenant->plan;

        if (! $plan) {
            return [
                'pkr'     => 0.0,
                'usd'     => 0.0,
                'aed'     => 0.0,
                'amounts' => [],
                'cycle'   => $cycle,
            ];
        }

        $amounts = [];
        foreach (TenantBillingRegion::supportedCurrencies() as $ccy) {
            try {
                $amounts[$ccy] = $this->resolveAmount($plan, $cycle, $ccy);
            } catch (\Throwable) {
                $amounts[$ccy] = 0.0;
            }
        }

        return [
            'pkr'     => $amounts['PKR'] ?? 0.0,
            'usd'     => $amounts['USD'] ?? 0.0,
            'aed'     => $amounts['AED'] ?? 0.0,
            'amounts' => $amounts,
            'cycle'   => $cycle,
        ];
    }

    public function jazzCashConfigured(): bool
    {
        return app(PlatformBillingSettingsService::class)->isReady('jazzcash');
    }

    public function bankTransferConfigured(string $currency): bool
    {
        $currency = strtolower($currency);

        if ($currency === 'pkr') {
            return app(PlatformBillingSettingsService::class)->isReady('meezan');
        }

        $bank = config("services.bank_transfer.{$currency}", []);

        return ! empty($bank['bank_name'])
            && ! empty($bank['account_title'])
            && ! empty($bank['account_number']);
    }

    private function usdListPrice(PackagePricing $plan, string $billingCycle): float
    {
        return $billingCycle === 'yearly'
            ? (float) $plan->yearly_price
            : (float) $plan->monthly_price;
    }
}
