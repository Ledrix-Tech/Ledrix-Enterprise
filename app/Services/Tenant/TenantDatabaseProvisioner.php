<?php

namespace App\Services\Tenant;

use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * F-28: Create and migrate a dedicated CRM database for one tenant.
 */
class TenantDatabaseProvisioner
{
    public function databaseNameFor(Tenant $tenant): string
    {
        $prefix = (string) config('tenancy.database_prefix', 'ledrix_tenant_');

        return $prefix.$tenant->id;
    }

    public function provision(Tenant $tenant, ?int $actorId = null): string
    {
        if (! config('tenancy.db_isolation_enabled')) {
            throw new RuntimeException('Tenant DB isolation is disabled (TENANT_DB_ISOLATION=false).');
        }

        if (filled($tenant->crm_database)) {
            return (string) $tenant->crm_database;
        }

        $database = $this->databaseNameFor($tenant);

        $this->createDatabase($database);
        $this->runCrmMigrations($database);

        $tenant->forceFill(['crm_database' => $database])->save();

        AuditLog::record(
            action: 'tenant.database_provisioned',
            tenantId: (int) $tenant->id,
            actorType: $actorId ? 'super_admin' : 'system',
            actorId: $actorId,
            actorName: $actorId ? 'Super Admin' : 'System',
            context: [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => "Dedicated CRM database provisioned: {$database}",
                'after'        => ['crm_database' => $database],
            ]
        );

        return $database;
    }

    private function createDatabase(string $database): void
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $database) ?? $database;

        if ($safeName !== $database) {
            throw new RuntimeException('Invalid tenant database name.');
        }

        $connection = config('database.default') === 'central' ? 'primary' : config('database.default');

        try {
            DB::connection($connection)->statement(
                'CREATE DATABASE IF NOT EXISTS `'.$safeName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
            );
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to create tenant database: '.$e->getMessage(), 0, $e);
        }
    }

    private function runCrmMigrations(string $database): void
    {
        Config::set('database.connections.tenant_provision', array_merge(
            config('database.connections.primary'),
            ['database' => $database]
        ));

        DB::purge('tenant_provision');

        Artisan::call('migrate', [
            '--database' => 'tenant_provision',
            '--force'    => true,
        ]);
    }
}
