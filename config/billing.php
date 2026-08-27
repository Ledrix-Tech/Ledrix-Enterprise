<?php

/**
 * SaaS subscription billing: currencies, FX base, and region → rail mapping.
 *
 * Pakistan (PKR) → Meezan bank transfer.
 * International → Stripe (UAE account later; charge in tenant currency e.g. USD/AED).
 */
return [

    /** Reporting / FX pivot currency (MRR/ARR converted into this). */
    'base_currency' => env('BILLING_BASE_CURRENCY', 'USD'),

    /**
     * Currencies tenants may prefer / be charged in.
     * PKR = Meezan; others = Stripe Checkout.
     */
    'supported_currencies' => ['USD', 'PKR', 'AED', 'EUR', 'GBP'],

    /** ISO country (or common aliases) → default billing currency. */
    'country_currency' => [
        'PK'       => 'PKR',
        'PAK'      => 'PKR',
        'PAKISTAN' => 'PKR',
        'AE'       => 'AED',
        'ARE'      => 'AED',
    ],

    /**
     * Fallback FX quotes when no platform_fx_rates row exists (quote per 1 USD).
     * JazzCash env rate still wins for USD→PKR when DB empty.
     */
    'default_rates_from_usd' => [
        'PKR' => (float) env('JAZZCASH_USD_TO_PKR_RATE', env('BILLING_USD_TO_PKR_RATE', 280)),
        'AED' => (float) env('BILLING_USD_TO_AED_RATE', 3.6725),
        'EUR' => (float) env('BILLING_USD_TO_EUR_RATE', 0.92),
        'GBP' => (float) env('BILLING_USD_TO_GBP_RATE', 0.79),
    ],

    /** Stripe zero-decimal currencies (amount already in smallest unit). */
    'zero_decimal_currencies' => [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA',
        'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ],

    /** Currencies we round invoice totals to whole units (display). */
    'whole_unit_currencies' => ['PKR', 'JPY', 'KRW'],
];
