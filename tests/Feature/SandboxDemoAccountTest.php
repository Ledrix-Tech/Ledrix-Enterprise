<?php

namespace Tests\Feature;

use App\Models\Central\DemoAccount;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Models\Demos\Admin;
use App\Models\Demos\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class SandboxDemoAccountTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->bootDemosSqlite();
    }

    private function bootDemosSqlite(): void
    {
        config([
            'sandbox.connection' => 'demos_db',
            'sandbox.database'   => 'ledrix_demos',
            'database.connections.demos_db' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        app('db')->purge('demos_db');
        app('db')->reconnect('demos_db');

        $this->artisan('migrate', [
            '--database' => 'demos_db',
            '--path'     => 'database/migrations/demos',
        ]);
    }

    public function test_sandbox_register_form_loads(): void
    {
        $this->get(route('sandbox.register'))
            ->assertOk()
            ->assertSee('Create a sandbox login')
            ->assertSee('Start a real trial')
            ->assertDontSee('billing_name', false);
    }

    public function test_sandbox_register_creates_demo_account_not_a_trial_tenant(): void
    {
        $tenantsBefore = Tenant::query()->count();

        $this->post(route('sandbox.register.store'), [
            'name'                  => 'Alex Rivera',
            'email'                 => 'alex.sandbox@example.com',
            'password'              => 'password12',
            'password_confirmation' => 'password12',
            'company'               => 'North Agency',
        ])->assertRedirect(route('admin.index.get'));

        $this->assertAuthenticated('demo');

        $account = DemoAccount::query()->where('email', 'alex.sandbox@example.com')->first();
        $this->assertNotNull($account);
        $this->assertSame('Alex Rivera', $account->name);
        $this->assertSame('active', $account->status);

        $this->assertNull(Tenant::query()->where('email', 'alex.sandbox@example.com')->first());
        $this->assertSame($tenantsBefore + 1, Tenant::query()->count());

        $sandbox = Tenant::query()->where('slug', 'ledrix-demo')->first();
        $this->assertNotNull($sandbox);
        $this->assertFalse((bool) $sandbox->trial_used);
        $this->assertNull($sandbox->trial_ends_at);
        $this->assertNotNull($sandbox->email_verified_at);
        $this->assertSame('active', $sandbox->status);
        $this->assertTrue((bool) ($sandbox->meta['is_sandbox'] ?? false));
        $this->assertSame('ledrix_demos', $sandbox->crm_database);

        $membership = TenantMembership::query()->where('tenant_id', $sandbox->id)->first();
        $this->assertNotNull($membership);
        $this->assertSame('active', $membership->status);
        $this->assertNull($membership->trial_start);
        $this->assertNull($membership->trial_end);

        $this->assertAuthenticated('admin');
        $this->assertNotNull(
            Admin::withoutGlobalScopes()->where('email', config('sandbox.admin_email'))->first()
        );
        $this->assertGuest('tenant');
    }

    public function test_sandbox_session_cannot_open_organization_billing(): void
    {
        $this->post(route('sandbox.register.store'), [
            'name'                  => 'Sam Demo',
            'email'                 => 'sam.sandbox@example.com',
            'password'              => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect();

        $this->get(route('admin.org.billing'))
            ->assertRedirect(route('admin.index.get'));

        $this->get(route('admin.org.plan'))
            ->assertRedirect(route('admin.index.get'));

        $this->get(route('tenant.billing'))
            ->assertRedirect(route('admin.index.get'));
    }

    public function test_sandbox_seeds_tour_brands_and_does_not_mark_trial_used(): void
    {
        $this->post(route('sandbox.register.store'), [
            'name'                  => 'Jordan Demo',
            'email'                 => 'jordan.sandbox@example.com',
            'password'              => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect();

        $sandbox = Tenant::query()->where('slug', 'ledrix-demo')->first();
        $this->assertNotNull($sandbox);
        $this->assertFalse((bool) $sandbox->trial_used);

        $this->assertTrue(
            Brand::withoutGlobalScopes()
                ->where('tenant_id', $sandbox->id)
                ->where('brand_name', 'Demo Atlas')
                ->exists()
        );
        $this->assertTrue(
            Brand::withoutGlobalScopes()
                ->where('tenant_id', $sandbox->id)
                ->where('brand_name', 'Demo Northstar')
                ->exists()
        );
    }
}
