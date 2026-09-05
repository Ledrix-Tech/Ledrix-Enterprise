<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * F-28: Use the tenant CRM database for models. Keep `primary` for sessions/jobs.
 */
class SwitchTenantDatabaseConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('tenancy.db_isolation_enabled')) {
            return $next($request);
        }

        $tenantId = TenantContext::resolve();
        if (! $tenantId) {
            return $next($request);
        }

        $tenant = Tenant::on('central')->find($tenantId);
        TenantDatabase::activate($tenant);

        try {
            return $next($request);
        } finally {
            TenantDatabase::deactivate();
        }
    }
}
