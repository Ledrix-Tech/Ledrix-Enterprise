<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect owners/admins to 2FA setup when forced by config/security.php (F-13).
 */
class EnsureForcedTwoFactor
{
    public function handle(Request $request, Closure $next, string $surface = 'super_admin'): Response
    {
        if ($surface === 'super_admin') {
            return $this->handleSuperAdmin($request, $next);
        }

        if ($surface === 'admin') {
            return $this->handleTenantAdmin($request, $next);
        }

        return $next($request);
    }

    private function handleSuperAdmin(Request $request, Closure $next): Response
    {
        if (! config('security.force_super_admin_owner_2fa')) {
            return $next($request);
        }

        $user = Auth::guard('super_admin')->user();
        if (! $user || ! $user->isOwner()) {
            return $next($request);
        }

        if ($user->two_factor_secret) {
            return $next($request);
        }

        if ($request->routeIs('super-admin.2fa.setup', 'super-admin.2fa.enable', 'super-admin.logout')) {
            return $next($request);
        }

        return redirect()
            ->route('super-admin.2fa.setup')
            ->with('warning', 'Platform owner accounts must enable two-factor authentication before continuing.');
    }

    private function handleTenantAdmin(Request $request, Closure $next): Response
    {
        if (! config('security.force_tenant_admin_2fa')) {
            return $next($request);
        }

        // Do not force during SA impersonation — operator may not have tenant 2FA.
        if (session()->has('impersonator_super_admin_id')) {
            return $next($request);
        }

        $user = Auth::guard('admin')->user();
        if (! $user || ($user->role ?? null) !== 'admin') {
            return $next($request);
        }

        if ($user->two_factor_secret) {
            return $next($request);
        }

        if ($request->routeIs(
            'admin.2fa.setup',
            'admin.2fa.enable',
            'admin.logout',
            'admin.impersonation.stop'
        )) {
            return $next($request);
        }

        return redirect()
            ->route('admin.2fa.setup')
            ->with('warning', 'Organization owner accounts must enable two-factor authentication before continuing.');
    }
}
