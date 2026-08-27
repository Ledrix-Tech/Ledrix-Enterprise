<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Services\Security\OidcClientService;
use App\Services\Security\PlatformSsoSettingsService;
use App\Services\Tenant\SubscriptionAccessService;
use App\Support\TenantContext;
use App\Support\TenantHostResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class AdminSsoController extends Controller
{
    public function redirect(Request $request, OidcClientService $oidc)
    {
        if (! $oidc->isEnabled()) {
            abort(404);
        }

        $state = $oidc->generateState();
        $redirectUri = route('admin.sso.callback');

        $request->session()->put(config('sso.session_state_key'), $state);
        $request->session()->put(config('sso.session_flow_key'), 'admin');

        try {
            $url = $oidc->buildAuthorizeUrl($state, $redirectUri);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'SSO is temporarily unavailable. '.$e->getMessage());
        }

        return redirect()->away($url);
    }

    public function callback(
        Request $request,
        OidcClientService $oidc,
        PlatformSsoSettingsService $settings,
        SubscriptionAccessService $access
    ) {
        if (! $oidc->isEnabled()) {
            abort(404);
        }

        $expectedState = $request->session()->pull(config('sso.session_state_key'));
        $flow = $request->session()->pull(config('sso.session_flow_key'));

        if ($flow !== 'admin' || ! $oidc->validateState($request->query('state'), $expectedState)) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'SSO login failed: invalid or expired state. Please try again.');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'SSO login was denied by the identity provider.');
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'SSO login failed: missing authorization code.');
        }

        try {
            $identity = $oidc->authenticateWithCode($code, route('admin.sso.callback'));
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'SSO login failed. '.$e->getMessage());
        }

        TenantContext::clear();
        session()->forget('tenant_id');

        /** @var Admin|null $admin */
        $adminQuery = Admin::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [strtolower($identity['email'])]);

        if ($tenantId = TenantHostResolver::resolveTenantId($request)) {
            $adminQuery->where('tenant_id', $tenantId);
        }

        $admin = $adminQuery->get()
            ->sortByDesc(fn (Admin $candidate) => (int) (bool) $candidate->tenant_id)
            ->first();

        if (! $admin && TenantHostResolver::resolveTenantId($request) !== null) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'No CRM admin account exists for this SSO email on this organization domain.');
        }

        if (! $admin) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'No CRM admin account exists for this SSO email. Accounts are not auto-provisioned.');
        }

        if (! $admin->tenant_id) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'No organization workspace is linked to this admin account.');
        }

        if (($admin->role ?? null) === 'demo') {
            return redirect()
                ->route('pricing.get')
                ->with('info', 'Demo accounts are no longer available. Start your free 14-day trial instead.');
        }

        // Prefer role=admin for CRM SSO; finance/up_admin may still sign in when present.
        if ($admin->two_factor_secret) {
            session([
                'admin_2fa_pending_id' => $admin->id,
                'admin_2fa_remember' => false,
            ]);

            return redirect()->route('admin.2fa.challenge');
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        session(['tenant_id' => $admin->tenant_id]);
        TenantContext::set((int) $admin->tenant_id);

        $tenant = Tenant::query()->find($admin->tenant_id);
        if (! $tenant) {
            Auth::guard('admin')->logout();
            session()->forget(['tenant_id', 'role']);
            TenantContext::clear();

            return redirect()
                ->route('admin.login.get')
                ->with('error', 'Organization workspace was not found for this account.');
        }

        if (! $access->canUseCrm($tenant)) {
            if (($admin->role ?? null) === 'admin' && $access->canAccessOrgBilling($tenant)) {
                return redirect()
                    ->route('admin.org.billing')
                    ->with('error', 'Your subscription is not active. Renew below to restore CRM access.');
            }

            Auth::guard('admin')->logout();
            session()->forget(['tenant_id', 'role']);
            TenantContext::clear();

            return redirect()
                ->route('admin.login.get')
                ->with('error', 'Your subscription is not active. Please renew via your organization portal.');
        }

        AuditLog::record(
            action: 'sso.login',
            tenantId: (int) $admin->tenant_id,
            actorType: 'admin',
            actorId: $admin->id,
            actorName: $admin->name,
            context: [
                'description' => 'CRM admin signed in via OIDC SSO',
                'after' => [
                    'provider' => $settings->settings()['provider_name'] ?? 'OIDC',
                    'email' => $admin->email,
                    'sub' => $identity['sub'],
                ],
            ],
        );

        if ($admin->role === 'finance') {
            session(['role' => 'finance']);

            return redirect()
                ->route('admin.brand-payments.get')
                ->with('success', 'Signed in with SSO as Finance Manager.');
        }

        return redirect()
            ->route('admin.index.get')
            ->with('success', 'Signed in with SSO successfully.');
    }
}
