<?php

namespace App\Support;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Point the `tenant` CRM connection at a dedicated database.
 * Never retarget `primary` — sessions, jobs, and cache stay on the shared DB.
 */
final class TenantDatabase
{
    private static ?string $previousDefault = null;

    public static function activate(?Tenant $tenant): void
    {
        if (! config('tenancy.db_isolation_enabled') || ! $tenant || ! filled($tenant->crm_database)) {
            return;
        }

        $shared = config('database.connections.primary', []);

        Config::set('database.connections.tenant', array_merge($shared, [
            'database' => (string) $tenant->crm_database,
        ]));
        DB::purge('tenant');

        if (static::$previousDefault === null) {
            static::$previousDefault = (string) config('database.default', 'primary');
        }

        Config::set('database.default', 'tenant');
    }

    public static function deactivate(): void
    {
        if (static::$previousDefault !== null) {
            Config::set('database.default', static::$previousDefault);
            static::$previousDefault = null;
        }

        DB::purge('tenant');
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function using(?Tenant $tenant, callable $callback): mixed
    {
        $previous = config('database.default');
        $stored = static::$previousDefault;
        self::activate($tenant);

        try {
            return $callback();
        } finally {
            Config::set('database.default', $previous);
            static::$previousDefault = $stored;
            DB::purge('tenant');
        }
    }
}
