<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Support\TenantDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantDatabaseSwitchTest extends TestCase
{
    public function test_activate_does_not_retarget_primary_connection(): void
    {
        Config::set('tenancy.db_isolation_enabled', true);
        Config::set('database.default', 'primary');
        Config::set('database.connections.primary.database', 'ledrix_primary');

        $tenant = new Tenant([
            'id'           => 99,
            'crm_database' => 'ledrix_tenant_99',
        ]);
        $tenant->exists = true;

        TenantDatabase::activate($tenant);

        $this->assertSame('ledrix_primary', config('database.connections.primary.database'));
        $this->assertSame('ledrix_tenant_99', config('database.connections.tenant.database'));
        $this->assertSame('tenant', config('database.default'));

        TenantDatabase::deactivate();

        $this->assertSame('primary', config('database.default'));
        $this->assertSame('ledrix_primary', config('database.connections.primary.database'));
    }

    public function test_session_is_not_the_tenant_connection(): void
    {
        $this->assertNotSame('tenant', config('session.connection'));
    }
}
