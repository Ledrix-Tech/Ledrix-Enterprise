<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Services\Tenant\TenantDatabaseProvisioner;
use Illuminate\Console\Command;

class ProvisionTenantDatabaseCommand extends Command
{
    protected $signature = 'tenants:provision-db {tenantId : Central tenant ID}';

    protected $description = 'Provision a dedicated CRM database for one tenant (F-28)';

    public function handle(TenantDatabaseProvisioner $provisioner): int
    {
        if (! config('tenancy.db_isolation_enabled')) {
            $this->error('TENANT_DB_ISOLATION is disabled. Set TENANT_DB_ISOLATION=true in .env first.');

            return self::FAILURE;
        }

        $tenant = Tenant::on('central')->findOrFail((int) $this->argument('tenantId'));

        $database = $provisioner->provision($tenant);

        $this->info("Provisioned CRM database: {$database} for tenant #{$tenant->id} ({$tenant->name})");

        return self::SUCCESS;
    }
}
