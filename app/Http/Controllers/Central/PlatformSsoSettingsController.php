<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Security\PlatformSsoSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformSsoSettingsController extends Controller
{
    public function edit(PlatformSsoSettingsService $sso)
    {
        $settings = $sso->settings();

        return view('central.pages.sso-settings', [
            'migrationRequired' => ! $sso->tableReady(),
            'settings' => $settings,
            'defaultCallback' => route('super-admin.sso.callback'),
            'adminCallback' => route('admin.sso.callback'),
        ]);
    }

    public function update(Request $request, PlatformSsoSettingsService $sso)
    {
        if (! $sso->tableReady()) {
            return back()->with(
                'error',
                'Run central migrations first: php artisan migrate --database=central --path=database/migrations/central --force'
            );
        }

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider_name' => ['nullable', 'string', 'max:120'],
            'issuer_url' => ['nullable', 'url', 'max:500'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:2000'],
            'redirect_uri' => ['nullable', 'url', 'max:500'],
            'scopes' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->boolean('enabled')) {
            $request->validate([
                'issuer_url' => ['required', 'url', 'max:500'],
                'client_id' => ['required', 'string', 'max:255'],
            ]);

            $existing = $sso->settings();
            if (! filled($validated['client_secret'] ?? null) && ! ($existing['has_client_secret'] ?? false)) {
                return back()
                    ->withInput()
                    ->withErrors(['client_secret' => 'Client secret is required when enabling SSO.']);
            }
        }

        $sso->update([
            'enabled' => $request->boolean('enabled'),
            'provider_name' => $validated['provider_name'] ?? null,
            'issuer_url' => $validated['issuer_url'] ?? null,
            'client_id' => $validated['client_id'] ?? null,
            'client_secret' => $validated['client_secret'] ?? null,
            'redirect_uri' => $validated['redirect_uri'] ?? null,
            'scopes' => $validated['scopes'] ?? 'openid profile email',
            'audience' => $validated['audience'] ?? null,
        ], Auth::guard('super_admin')->id());

        return back()->with('success', 'SSO settings saved.');
    }
}
