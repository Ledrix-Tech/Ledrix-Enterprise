<?php

namespace App\Services\Billing;

/**
 * Currency rounding + Stripe minor-unit helpers.
 */
class BillingMoney
{
    public static function decimalPlaces(string $currency): int
    {
        $currency = strtoupper($currency);

        if (in_array($currency, config('billing.whole_unit_currencies', ['PKR']), true)) {
            return 0;
        }

        if (in_array($currency, config('billing.zero_decimal_currencies', []), true)) {
            return 0;
        }

        return 2;
    }

    public static function round(float $amount, string $currency): float
    {
        return round($amount, self::decimalPlaces($currency));
    }

    public static function format(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);
        $decimals = self::decimalPlaces($currency);

        return $currency.' '.number_format($amount, $decimals);
    }

    /** Stripe / processor smallest currency unit. */
    public static function toMinorUnits(float $amount, string $currency): int
    {
        $currency = strtoupper($currency);

        if (in_array($currency, config('billing.zero_decimal_currencies', []), true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }
}
