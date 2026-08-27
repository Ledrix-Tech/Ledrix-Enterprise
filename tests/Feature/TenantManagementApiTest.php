<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantMembership;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantManagementApiTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureBillingTables();
    }

    public function test_company_membership_invoices_usage_with_star_token(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Api Co',
            'slug'     => 'api-co',
            'email'    => 'api-co@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        TenantMembership::query()->create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => 1,
            'billing_cycle' => 'monthly',
            'amount'        => 99,
            'currency'      => 'USD',
            'api_key'       => 'key_'.uniqid(),
            'start_date'    => now()->toDateString(),
            'end_date'      => now()->addMonth()->toDateString(),
            'status'        => 'active',
        ]);

        TenantInvoice::query()->create([
            'tenant_id'      => $tenant->id,
            'invoice_number' => 'INV-100',
            'plan_name'      => 'Agency',
            'billing_cycle'  => 'monthly',
            'amount'         => 99,
            'currency'       => 'USD',
            'tax_amount'     => 0,
            'total_amount'   => 99,
            'status'         => 'paid',
            'issued_at'      => now(),
            'paid_at'        => now(),
        ]);

        [$plain] = TenantApiToken::generate((int) $tenant->id, 'CI', ['*']);

        $this->withToken($plain)
            ->getJson('/api/v1/company')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'api-co');

        $this->withToken($plain)
            ->getJson('/api/v1/membership')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withToken($plain)
            ->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonPath('data.0.invoice_number', 'INV-100');

        $this->withToken($plain)
            ->getJson('/api/v1/usage')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_ability_gate_rejects_missing_scope(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Scoped',
            'slug'     => 'scoped',
            'email'    => 'scoped@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        [$plain] = TenantApiToken::generate((int) $tenant->id, 'Limited', ['company:read']);

        $this->withToken($plain)
            ->getJson('/api/v1/company')
            ->assertOk();

        $this->withToken($plain)
            ->getJson('/api/v1/invoices')
            ->assertForbidden();
    }

    private function ensureBillingTables(): void
    {
        if (! Schema::connection('central')->hasTable('tenant_memberships')) {
            Schema::connection('central')->create('tenant_memberships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('billing_cycle')->default('monthly');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->string('api_key', 64)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_invoices')) {
            Schema::connection('central')->create('tenant_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('membership_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->string('plan_name')->nullable();
                $table->string('billing_cycle')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('status')->default('issued');
                $table->string('pdf_path')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_usage_snapshots')) {
            Schema::connection('central')->create('tenant_usage_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->unsignedInteger('total_brands')->default(0);
                $table->unsignedInteger('total_sellers')->default(0);
                $table->unsignedInteger('total_admins')->default(0);
                $table->unsignedInteger('total_clients')->default(0);
                $table->unsignedInteger('total_orders')->default(0);
                $table->unsignedInteger('leads_this_month')->default(0);
                $table->unsignedInteger('storage_used_mb')->default(0);
                $table->timestamps();
            });
        }
    }
}
