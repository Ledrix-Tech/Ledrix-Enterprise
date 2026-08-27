<?php

namespace Tests\Feature;

use App\Models\Central\PackagePricing;
use App\Models\Central\PlatformWebhookEvent;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Models\Central\TenantFeatureOverride;
use App\Services\Billing\PlatformWebhookRecorder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class SuperAdminOpsTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        Mail::fake();
    }

    public function test_admin_can_create_and_revoke_api_token(): void
    {
        $admin = $this->makeAdmin('admin');
        $tenant = Tenant::query()->create([
            'name'     => 'Acme',
            'slug'     => 'acme',
            'email'    => 'acme@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $this->actingAs($admin, 'super_admin')
            ->post(route('super-admin.tenant.api-tokens.store', $tenant->id), [
                'name'      => 'CI Token',
                'abilities' => '*',
            ])
            ->assertRedirect()
            ->assertSessionHas('new_api_token');

        $token = TenantApiToken::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($token);
        $this->assertSame('active', $token->status);

        $this->actingAs($admin, 'super_admin')
            ->post(route('super-admin.tenant.api-tokens.revoke', [$tenant->id, $token->id]))
            ->assertRedirect();

        $this->assertSame('revoked', $token->fresh()->status);
    }

    public function test_webhook_recorder_is_idempotent(): void
    {
        $calls = 0;
        $recorder = app(PlatformWebhookRecorder::class);

        $recorder->recordAndProcess(
            'stripe',
            'evt_test_1',
            'checkout.session.return',
            ['ok' => true],
            null,
            function () use (&$calls) {
                $calls++;
            }
        );

        $recorder->recordAndProcess(
            'stripe',
            'evt_test_1',
            'checkout.session.return',
            ['ok' => true],
            null,
            function () use (&$calls) {
                $calls++;
            }
        );

        $this->assertSame(1, $calls);
        $this->assertSame(1, PlatformWebhookEvent::query()->count());
        $this->assertTrue(PlatformWebhookEvent::alreadyHandled('evt_test_1'));
    }

    public function test_owner_can_archive_unused_pricing_package(): void
    {
        $owner = $this->makeAdmin('owner');
        $package = PackagePricing::query()->create([
            'name'          => 'Temp',
            'slug'          => 'crm-temp',
            'monthly_price' => 10,
            'yearly_price'  => 100,
            'status'        => 'active',
            'is_public'     => true,
        ]);

        $this->actingAs($owner, 'super_admin')
            ->delete(route('super-admin.pricing-packages.destroy', $package->id))
            ->assertRedirect();

        $this->assertNull(PackagePricing::query()->find($package->id));
    }

    public function test_non_owner_cannot_open_team_page(): void
    {
        $admin = $this->makeAdmin('admin');

        $this->actingAs($admin, 'super_admin')
            ->get(route('super-admin.team.get'))
            ->assertForbidden();
    }

    public function test_admin_can_set_verify_and_clear_tenant_custom_domain(): void
    {
        $this->ensureCustomDomainSchema();

        $admin = $this->makeAdmin('admin');
        $tenant = Tenant::query()->create([
            'name'     => 'Domain Co',
            'slug'     => 'domain-co',
            'email'    => 'domain@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $this->actingAs($admin, 'super_admin')
            ->from(route('super-admin.tenant.show', $tenant->id))
            ->put(route('super-admin.tenant.domain.update', $tenant->id), [
                'custom_domain' => 'crm.agency-test.com',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame('crm.agency-test.com', $tenant->custom_domain);
        $this->assertFalse((bool) $tenant->custom_domain_verified);
        $this->assertNotNull(
            TenantFeatureOverride::query()->where('tenant_id', $tenant->id)->where('feature_custom_domain', true)->first()
        );

        $this->actingAs($admin, 'super_admin')
            ->post(route('super-admin.tenant.domain.verify', $tenant->id))
            ->assertRedirect();

        $this->assertTrue((bool) $tenant->fresh()->custom_domain_verified);

        $this->actingAs($admin, 'super_admin')
            ->post(route('super-admin.tenant.domain.unverify', $tenant->id))
            ->assertRedirect();

        $this->assertFalse((bool) $tenant->fresh()->custom_domain_verified);

        $this->actingAs($admin, 'super_admin')
            ->put(route('super-admin.tenant.domain.update', $tenant->id), [
                'custom_domain' => '',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertNull($tenant->custom_domain);
        $this->assertFalse((bool) $tenant->custom_domain_verified);
    }

    private function ensureCustomDomainSchema(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'custom_domain')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->string('custom_domain')->nullable();
                $table->boolean('custom_domain_verified')->default(false);
            });
        } elseif (! Schema::connection('central')->hasColumn('tenants', 'custom_domain_verified')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->boolean('custom_domain_verified')->default(false);
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_feature_overrides')) {
            Schema::connection('central')->create('tenant_feature_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->boolean('feature_custom_domain')->nullable();
                $table->string('override_reason')->nullable();
                $table->unsignedBigInteger('overridden_by')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function makeAdmin(string $role): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name'     => ucfirst($role),
            'email'    => $role . '@example.com',
            'password' => Hash::make('Password1!'),
            'role'     => $role,
            'status'   => 'active',
        ]);
    }
}
