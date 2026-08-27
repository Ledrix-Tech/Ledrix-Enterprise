<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use App\Support\TenantHostResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * F-27: Bind verified custom-domain (and slug subdomain) requests to a tenant workspace.
 */
class ResolveTenantFromHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantHostResolver::resolveTenant($request);

        if ($tenant) {
            $tenantId = (int) $tenant->id;
            $request->attributes->set('tenant_host_resolved_id', $tenantId);
            $request->attributes->set('tenant_host_custom_domain', TenantHostResolver::isCustomDomainHost($request));

            if ($request->attributes->get('tenant_host_custom_domain')) {
                session(['tenant_id' => $tenantId]);
            } elseif (! session()->has('tenant_id')) {
                session(['tenant_id' => $tenantId]);
            }

            TenantContext::set($tenantId);
        }

        return $next($request);
    }
}
