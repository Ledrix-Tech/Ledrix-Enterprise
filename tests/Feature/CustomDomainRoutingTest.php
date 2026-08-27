<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * F-27: Custom domain host → tenant routing.
 *
 * @group enterprise
 */
class CustomDomainRoutingTest extends TestCase
{
    use CreatesPortalUsers;
    use MigratesUpworkForTests;
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureCustomDomainColumns();
        $this->migrateUpworkTables();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
    }

    public function test_verified_custom_domain_sets_tenant_session_on_admin_login_page(): void
    {
        [$tenant] = $this->seedTenantAdmin();

        $tenant->forceFill([
            'custom_domain' => 'crm.test-agency.test',
            'custom_domain_verified' => true,
        ])->save();

        $this->get('http://crm.test-agency.test/admin/login')
            ->assertOk();

        $this->assertSame((int) $tenant->id, session('tenant_id'));
    }

    public function test_custom_domain_root_redirects_to_admin_login(): void
    {
        [$tenant] = $this->seedTenantAdmin();

        $tenant->forceFill([
            'custom_domain' => 'portal.test-agency.test',
            'custom_domain_verified' => true,
        ])->save();

        $this->get('http://portal.test-agency.test/')
            ->assertRedirect(route('admin.login.get'));
    }

    public function test_super_admin_is_blocked_on_custom_domain(): void
    {
        [$tenant] = $this->seedTenantAdmin();

        $tenant->forceFill([
            'custom_domain' => 'blocked.test-agency.test',
            'custom_domain_verified' => true,
        ])->save();

        $this->get('http://blocked.test-agency.test/super-admin/login')
            ->assertNotFound();
    }

    public function test_admin_login_on_custom_domain_scopes_to_host_tenant(): void
    {
        [$tenantA, $adminA] = $this->seedTenantAdmin();
        [$tenantB] = $this->seedTenantAdmin();

        $tenantA->forceFill([
            'custom_domain' => 'crm-a.test',
            'custom_domain_verified' => true,
        ])->save();

        Admin::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'name'      => 'Wrong Tenant Admin',
            'email'     => $adminA->email,
            'password'  => 'password',
            'role'      => 'admin',
        ]);

        $this->post('http://crm-a.test/admin/login', [
            'email'    => $adminA->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.index.get'));

        $this->assertAuthenticatedAs($adminA, 'admin');
    }

    /**
     * @return array{0: Tenant, 1: Admin}
     */
    private function seedTenantAdmin(): array
    {
        $plan = PackagePricing::query()->create([
            'name'          => 'Agency',
            'slug'          => 'agency-'.uniqid(),
            'monthly_price' => 99,
            'yearly_price'  => 990,
            'currency'      => 'USD',
            'trial_days'    => 14,
            'status'        => 'active',
        ]);

        $tenant = Tenant::query()->create([
            'plan_id'  => $plan->id,
            'name'     => 'Org Co',
            'slug'     => 'org-'.uniqid(),
            'email'    => 'org-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'email'     => 'owner-'.uniqid().'@example.com',
            'password'  => 'password',
        ]);

        return [$tenant->fresh(['plan']), $admin];
    }

    private function ensureCustomDomainColumns(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'custom_domain')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->string('custom_domain')->nullable();
                $table->boolean('custom_domain_verified')->default(false);
            });
        }
    }
}
