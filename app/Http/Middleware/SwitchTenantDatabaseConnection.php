<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * F-28: Switch the primary CRM connection to the tenant's dedicated database when configured.
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
        $database = $tenant?->crm_database;

        if (! filled($database)) {
            return $next($request);
        }

        Config::set('database.connections.primary.database', $database);
        DB::purge('primary');
        DB::reconnect('primary');

        try {
            return $next($request);
        } finally {
            Config::set('database.connections.primary.database', env('DB_PRIMARY_DATABASE', 'ledrix_primary'));
            DB::purge('primary');
        }
    }
}
