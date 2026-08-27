<?php

namespace App\Services\Billing;

use App\Models\Central\Tenant;

/**
 * Resolves tenant billing currency and payment rail.
 *
 * Detection order:
 * 1. preferred_billing_currency if in supported list
 * 2. country → currency map (PK→PKR, AE→AED, else base USD)
 * 3. billing.base_currency (USD)
 *
 * Rails: PKR → Meezan; other currencies → Stripe.
 */
class TenantBillingRegion
{
    public const CURRENCY_PKR = 'PKR';

    public const CURRENCY_USD = 'USD';

    public const CURRENCY_AED = 'AED';

    /** @return list<string> */
    public static function supportedCurrencies(): array
    {
        $list = config('billing.supported_currencies', [self::CURRENCY_USD, self::CURRENCY_PKR, self::CURRENCY_AED]);

        return array_values(array_unique(array_map(
            fn ($c) => strtoupper((string) $c),
            is_array($list) ? $list : []
        )));
    }

    public static function isSupportedCurrency(?string $currency): bool
    {
        $currency = strtoupper(trim((string) $currency));

        return $currency !== '' && in_array($currency, self::supportedCurrencies(), true);
    }

    public static function isPakistanCountry(?string $country): bool
    {
        $code = strtoupper(trim((string) $country));

        return in_array($code, ['PK', 'PAK', 'PAKISTAN'], true);
    }

    public static function currencyFromCountry(?string $country): string
    {
        $code = strtoupper(trim((string) $country));
        $map = (array) config('billing.country_currency', []);

        if ($code !== '' && isset($map[$code]) && self::isSupportedCurrency($map[$code])) {
            return strtoupper((string) $map[$code]);
        }

        if (self::isPakistanCountry($country)) {
            return self::CURRENCY_PKR;
        }

        return strtoupper((string) config('billing.base_currency', self::CURRENCY_USD));
    }

    public static function currencyForTenant(Tenant $tenant): string
    {
        $preferred = strtoupper(trim((string) ($tenant->preferred_billing_currency ?? '')));

        if (self::isSupportedCurrency($preferred)) {
            return $preferred;
        }

        return self::currencyFromCountry($tenant->country);
    }

    public static function isPakistanBuyer(Tenant $tenant): bool
    {
        return self::currencyForTenant($tenant) === self::CURRENCY_PKR;
    }

    /** True when Stripe (not Meezan) is the primary rail. */
    public static function usesStripe(Tenant $tenant): bool
    {
        return ! self::isPakistanBuyer($tenant);
    }

    public static function regionLabel(Tenant $tenant): string
    {
        $currency = self::currencyForTenant($tenant);

        if ($currency === self::CURRENCY_PKR) {
            return 'Pakistan (PKR · Meezan)';
        }

        if ($currency === self::CURRENCY_AED) {
            return 'UAE / Gulf (AED · Stripe)';
        }

        return 'International ('.$currency.' · Stripe)';
    }

    /**
     * Persist currency from country when unset (idempotent).
     */
    public static function syncPreferredCurrency(Tenant $tenant): string
    {
        $currency = self::currencyForTenant($tenant);

        if (strtoupper((string) $tenant->preferred_billing_currency) !== $currency) {
            $tenant->forceFill(['preferred_billing_currency' => $currency])->save();
        }

        return $currency;
    }
}
