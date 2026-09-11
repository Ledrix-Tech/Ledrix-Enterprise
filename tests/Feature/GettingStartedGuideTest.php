<?php

namespace Tests\Feature;

use App\Mail\TenantGettingStartedMail;
use App\Mail\TenantVerifyEmail;
use App\Models\Admin;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Services\Tenant\RegisterTenantService;
use App\Support\GettingStartedGuide;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class GettingStartedGuideTest extends TestCase
{
    use CreatesPortalUsers;
    use MigratesUpworkForTests;
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function afterRefreshingDatabase(): void
    {
        $this->migrateUpworkTables();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
        config(['tenancy.db_isolation_enabled' => false]);
    }

    public function test_help_page_shows_confirmed_setup_order(): void
    {
        [, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.getting-started'))
            ->assertOk()
            ->assertSee('Add your first Brand', false)
            ->assertSee('Signup does not create one', false)
            ->assertSee('admin login is not a Seller', false)
            ->assertSee('Client is created automatically when a lead arrives', false)
            ->assertSee('Changing lead status does not create a Client or an Order', false)
            ->assertSee('generate a link from the Lead', false)
            ->assertSee('Projects are optional and not created automatically', false)
            ->assertSee('guide you step-by-step inside the app', false)
            ->assertDontSee('id="gettingStartedModal"', false);
    }

    public function test_first_login_modal_shows_until_dismissed(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.settings'))
            ->assertOk()
            ->assertSee('id="gettingStartedModal"', false)
            ->assertSee('Got it, let’s go', false)
            ->assertSee('Close getting started guide', false)
            ->assertSee('Add your first Brand', false);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.getting-started.dismiss'))
            ->assertRedirect();

        $tenant->refresh();
        $this->assertTrue(GettingStartedGuide::isDismissed($tenant));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.settings'))
            ->assertOk()
            ->assertDontSee('id="gettingStartedModal"', false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.getting-started'))
            ->assertOk()
            ->assertSee('Add your first Brand', false);
    }

    public function test_closing_guide_hides_modal_for_this_visit_only(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.getting-started.dismiss'), ['persist' => '0'])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertFalse(GettingStartedGuide::isDismissed($tenant));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.settings'))
            ->assertOk()
            ->assertDontSee('id="gettingStartedModal"', false);
    }

    public function test_tenant_portal_has_getting_started_page(): void
    {
        [$tenant] = $this->seedTenantAdmin();

        $this->actingAs($tenant, 'tenant')
            ->get(route('tenant.getting-started'))
            ->assertOk()
            ->assertSee('Getting started', false)
            ->assertSee('Add a Seller on that Brand', false);
    }

    public function test_registration_sends_getting_started_email(): void
    {
        $this->ensureRegistrationSchema();
        Mail::fake();

        PackagePricing::query()->create([
            'name'          => 'CRM Basic',
            'slug'          => 'crm-basic',
            'monthly_price' => 29,
            'yearly_price'  => 290,
            'currency'      => 'USD',
            'trial_days'    => 7,
            'is_public'     => true,
            'status'        => 'active',
        ]);

        app(RegisterTenantService::class)->register([
            'pkg_slug'        => 'crm-basic',
            'name'            => 'North Agency',
            'email'           => 'north.agency@example.com',
            'password'        => 'password12',
            'phone'           => '+15555550100',
            'country'         => 'US',
            'billing_name'    => 'North Agency',
            'billing_email'   => 'billing.north@example.com',
            'billing_address' => '1 Market St',
            'billing_cycle'   => 'monthly',
        ]);

        Mail::assertSent(TenantVerifyEmail::class);
        Mail::assertSent(TenantGettingStartedMail::class, function (TenantGettingStartedMail $mail) {
            return $mail->hasTo('north.agency@example.com')
                && $mail->tenant->email === 'north.agency@example.com';
        });
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
            'is_public'     => true,
            'status'        => 'active',
        ]);

        $tenant = Tenant::query()->create([
            'plan_id'           => $plan->id,
            'name'              => 'Org Co',
            'slug'              => 'org-'.uniqid(),
            'email'             => 'org-'.uniqid().'@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
            'email_verified_at' => now(),
            'trial_ends_at'     => now()->addDays(7),
            'trial_used'        => true,
        ]);

        TenantMembership::query()->create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => 'monthly',
            'amount'        => 99,
            'currency'      => 'USD',
            'api_key'       => 'key_'.uniqid(),
            'start_date'    => now()->toDateString(),
            'end_date'      => now()->addMonth()->toDateString(),
            'trial_start'   => now()->toDateString(),
            'trial_end'     => now()->addDays(7)->toDateString(),
            'status'        => 'trialing',
            'renewed_by'    => 'tenant',
        ]);

        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'email'     => 'owner-'.uniqid().'@example.com',
        ]);

        return [$tenant->fresh(['plan']), $admin];
    }

    private function ensureRegistrationSchema(): void
    {
        $schema = Schema::connection('central');

        if (! $schema->hasColumn('tenants', 'phone')) {
            $schema->table('tenants', function (Blueprint $table) {
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('website')->nullable();
                $table->string('billing_name')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('billing_phone')->nullable();
                $table->string('billing_address')->nullable();
                $table->string('preferred_billing_currency', 3)->nullable();
                $table->string('registered_ip', 45)->nullable();
            });
        }

        if (! $schema->hasTable('tenant_email_verifications')) {
            $schema->create('tenant_email_verifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('email');
                $table->string('token', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('created_at')->nullable();
            });
        }
    }
}
