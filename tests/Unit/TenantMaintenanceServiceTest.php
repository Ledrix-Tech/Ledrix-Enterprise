<?php

namespace Tests\Unit;

use App\Models\Central\SystemAnnouncement;
use App\Models\Central\Tenant;
use App\Services\Tenant\TenantMaintenanceService;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantMaintenanceServiceTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
    }

    public function test_blocking_announcement_detected_for_all_target(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Acme',
            'slug'     => 'acme',
            'email'    => 'acme@example.com',
            'password' => bcrypt('secret'),
            'status'   => 'active',
        ]);

        SystemAnnouncement::query()->create([
            'title'          => 'CRM maintenance window',
            'message'        => 'Back at 03:00 UTC.',
            'type'           => 'maintenance',
            'target'         => 'all',
            'is_dismissible' => false,
            'blocks_crm'     => true,
            'status'         => 'active',
        ]);

        $service = app(TenantMaintenanceService::class);

        $this->assertTrue($service->isCrmBlocked($tenant));
        $this->assertSame('CRM maintenance window', $service->blockingAnnouncement($tenant)?->title);
    }

    public function test_non_blocking_announcement_does_not_block_crm(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Beta',
            'slug'     => 'beta',
            'email'    => 'beta@example.com',
            'password' => bcrypt('secret'),
            'status'   => 'active',
        ]);

        SystemAnnouncement::query()->create([
            'title'          => 'Heads up',
            'message'        => 'New feature shipping Friday.',
            'type'           => 'info',
            'target'         => 'all',
            'is_dismissible' => true,
            'blocks_crm'     => false,
            'status'         => 'active',
        ]);

        $this->assertFalse(app(TenantMaintenanceService::class)->isCrmBlocked($tenant));
    }
}
