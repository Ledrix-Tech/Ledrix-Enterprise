<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use App\Services\Security\OidcClientService;
use App\Services\Security\PlatformSsoSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class SsoController extends Controller
{
    public function redirect(Request $request, OidcClientService $oidc, PlatformSsoSettingsService $settings)
    {
        if (! $oidc->isEnabled()) {
            abort(404);
        }

        $state = $oidc->generateState();
        $redirectUri = $this->callbackUri($settings);

        $request->session()->put(config('sso.session_state_key'), $state);
        $request->session()->put(config('sso.session_flow_key'), 'super_admin');

        try {
            $url = $oidc->buildAuthorizeUrl($state, $redirectUri);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('super-admin.login.get')
                ->withErrors(['email' => 'SSO is temporarily unavailable. '.$e->getMessage()]);
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, OidcClientService $oidc, PlatformSsoSettingsService $settings)
    {
        if (! $oidc->isEnabled()) {
            abort(404);
        }

        $expectedState = $request->session()->pull(config('sso.session_state_key'));
        $flow = $request->session()->pull(config('sso.session_flow_key'));
        $request->session()->forget([config('sso.session_state_key'), config('sso.session_flow_key')]);

        if ($flow !== 'super_admin' || ! $oidc->validateState($request->query('state'), $expectedState)) {
            return redirect()
                ->route('super-admin.login.get')
                ->withErrors(['email' => 'SSO login failed: invalid or expired state. Please try again.']);
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('super-admin.login.get')
                ->withErrors(['email' => 'SSO login was denied by the identity provider.']);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return redirect()
                ->route('super-admin.login.get')
                ->withErrors(['email' => 'SSO login failed: missing authorization code.']);
        }

        try {
            $identity = $oidc->authenticateWithCode($code, $this->callbackUri($settings));
        } catch (RuntimeException $e) {
            return redirect()
                ->route('super-admin.login.get')
                ->withErrors(['email' => 'SSO login failed. '.$e->getMessage()]);
        }

        /** @var SuperAdmin|null $admin */
        $admin = SuperAdmin::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($identity['email'])])
            ->first();

        if (! $admin) {
            return redirect()
                ->route('super-admin.login.get')
                ->withErrors(['email' => 'No Super Admin account exists for this SSO email. Accounts are not auto-provisioned.']);
        }

        if (! $admin->isActive()) {
            return redirect()
                ->route('super-admin.login.get')
                ->withErrors(['email' => 'Your Super Admin account has been deactivated.']);
        }

        if ($admin->two_factor_secret) {
            $request->session()->put('sa_2fa_pending_id', $admin->id);
            $request->session()->put('sa_2fa_remember', false);

            return redirect()->route('super-admin.2fa.challenge');
        }

        Auth::guard('super_admin')->login($admin);
        $request->session()->regenerate();
        $admin->markSeen();

        AuditLog::record(
            action: 'sso.login',
            actorType: 'super_admin',
            actorId: $admin->id,
            actorName: $admin->name,
            context: [
                'description' => 'Super Admin signed in via OIDC SSO',
                'after' => [
                    'provider' => $settings->settings()['provider_name'] ?? 'OIDC',
                    'email' => $admin->email,
                    'sub' => $identity['sub'],
                ],
            ],
        );

        return redirect()
            ->intended(route('super-admin.index.get'))
            ->with('success', 'Welcome back, '.$admin->name);
    }

    private function callbackUri(PlatformSsoSettingsService $settings): string
    {
        $configured = $settings->settings()['redirect_uri'] ?? null;
        if (filled($configured)) {
            return (string) $configured;
        }

        return route('super-admin.sso.callback');
    }
}
