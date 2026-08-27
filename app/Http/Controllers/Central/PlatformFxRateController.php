<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Services\Billing\FxRateService;
use App\Services\Billing\TenantBillingRegion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlatformFxRateController extends Controller
{
    public function edit(FxRateService $fx)
    {
        if ($fx->tableReady()) {
            $fx->seedDefaultsIfEmpty(Auth::guard('super_admin')->id());
        }

        return view('central.pages.fx-rates', [
            'rates'               => $fx->allRates(),
            'baseCurrency'        => $fx->baseCurrency(),
            'supportedCurrencies' => TenantBillingRegion::supportedCurrencies(),
            'migrationRequired'   => ! $fx->tableReady(),
            'defaults'            => (array) config('billing.default_rates_from_usd', []),
        ]);
    }

    public function store(Request $request, FxRateService $fx)
    {
        if (! $fx->tableReady()) {
            return back()->with(
                'error',
                'Run central migrations first: php artisan migrate --database=central --path=database/migrations/central --force'
            );
        }

        $supported = TenantBillingRegion::supportedCurrencies();

        $validated = $request->validate([
            'base_currency'  => ['required', 'string', 'size:3', Rule::in($supported)],
            'quote_currency' => ['required', 'string', 'size:3', Rule::in($supported)],
            'rate'           => ['required', 'numeric', 'gt:0', 'max:999999'],
        ]);

        if (strtoupper($validated['base_currency']) === strtoupper($validated['quote_currency'])) {
            return back()->with('error', 'Base and quote currencies must differ.');
        }

        $adminId = Auth::guard('super_admin')->id();
        $row = $fx->upsert(
            $validated['base_currency'],
            $validated['quote_currency'],
            (float) $validated['rate'],
            $adminId,
        );

        AuditLog::record(
            'platform.fx_rate_updated',
            null,
            'super_admin',
            $adminId,
            Auth::guard('super_admin')->user()?->name,
            [
                'subject_type' => 'platform_fx_rate',
                'subject_id'   => $row->id,
                'description'  => sprintf(
                    'FX rate %s→%s set to %s',
                    $row->base_currency,
                    $row->quote_currency,
                    $row->rate
                ),
                'after' => [
                    'base'  => $row->base_currency,
                    'quote' => $row->quote_currency,
                    'rate'  => $row->rate,
                ],
            ]
        );

        return back()->with('success', 'FX rate saved.');
    }

    public function destroy(Request $request, FxRateService $fx)
    {
        $validated = $request->validate([
            'base_currency'  => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3'],
        ]);

        $fx->deletePair($validated['base_currency'], $validated['quote_currency']);

        return back()->with('success', 'FX rate removed. Config/env fallbacks may still apply.');
    }
}
