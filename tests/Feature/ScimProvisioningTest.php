<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * SCIM 2.0 user provisioning.
 *
 * @group enterprise
 */
class ScimProvisioningTest extends TestCase
{
    use CreatesPortalUsers;
    use MigratesUpworkForTests;
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->migrateUpworkTables();

        config([
            'scim.enabled' => true,
            'scim.bearer_token' => 'test-scim-token',
        ]);
    }

    public function test_scim_returns_404_when_disabled(): void
    {
        config(['scim.enabled' => false]);

        $this->getJson('/api/scim/v2/Users')
            ->assertNotFound();
    }

    public function test_scim_requires_bearer_token(): void
    {
        $this->getJson('/api/scim/v2/Users')
            ->assertUnauthorized();
    }

    public function test_scim_provisions_crm_admin(): void
    {
        [$tenant] = $this->seedTenantAdmin();

        $response = $this->postJson('/api/scim/v2/Users', [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'userName' => 'scim-admin@example.test',
            'displayName' => 'SCIM Admin',
            'active' => true,
            'externalId' => (string) $tenant->id,
        ], [
            'Authorization' => 'Bearer test-scim-token',
        ]);

        $response->assertCreated()
            ->assertJsonPath('userName', 'scim-admin@example.test');

        $this->assertDatabaseHas('admins', [
            'email' => 'scim-admin@example.test',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_scim_deactivates_crm_admin(): void
    {
        [$tenant] = $this->seedTenantAdmin();
        $admin = Admin::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();

        $this->patchJson('/api/scim/v2/Users/'.$admin->id, [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'],
            'Operations' => [
                ['op' => 'replace', 'value' => ['active' => false]],
            ],
        ], [
            'Authorization' => 'Bearer test-scim-token',
        ])->assertNoContent();

        $this->assertDatabaseMissing('admins', ['id' => $admin->id]);
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
            'name'     => 'SCIM Org',
            'slug'     => 'scim-'.uniqid(),
            'email'    => 'scim-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $admin = $this->createAdmin([
            'tenant_id' => $tenant->id,
            'email'     => 'seed-'.uniqid().'@example.com',
            'password'  => 'password',
        ]);

        return [$tenant, $admin];
    }
}
