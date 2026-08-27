<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use App\Services\Tenant\TenantFeatureService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Client portal auth. End-customers may keep viewing project data even when
 * the tenant's Ledrix SaaS subscription has expired — only Admin/Seller CRM
 * is billing-gated. Suspended/cancelled tenants still block the portal.
 */
class ClientMiddleware
{
    public function __construct(
        private TenantFeatureService $tenantFeatures,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('client')->check()) {
            return redirect()->route('client.login.get')
                ->with('error', 'You don’t have access to the Portal.');
        }

        $client = Auth::guard('client')->user();

        if ($client->status !== 'Active') {
            Auth::guard('client')->logout();
            session()->forget(['tenant_id', 'role']);

            return redirect()->route('client.login.get')
                ->with('error', 'Your account is inactive. Please contact support.');
        }

        if (! $client->hasPortalAccess()) {
            Auth::guard('client')->logout();
            session()->forget(['tenant_id', 'role']);

            return redirect()->route('client.login.get')
                ->with('error', 'Portal access has not been enabled for your account.');
        }

        if ($client->tenant_id) {
            session(['tenant_id' => (int) $client->tenant_id]);
            TenantContext::set((int) $client->tenant_id);

            if (! $this->tenantFeatures->enabled('client_portal', (int) $client->tenant_id)) {
                Auth::guard('client')->logout();
                session()->forget(['tenant_id', 'role']);
                TenantContext::clear();

                return redirect()->route('client.login.get')
                    ->with('error', 'Client portal is not included in your organization plan.');
            }

            $tenant = Tenant::query()->find((int) $client->tenant_id);
            if ($tenant && ($tenant->isSuspended() || $tenant->isCancelled())) {
                Auth::guard('client')->logout();
                session()->forget(['tenant_id', 'role']);
                TenantContext::clear();

                return redirect()->route('client.login.get')
                    ->with('error', 'This workspace is currently unavailable. Please contact your account manager.');
            }
        }

        return $next($request);
    }
}
