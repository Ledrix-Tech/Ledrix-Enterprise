<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Services\Billing\BillingMoney;
use App\Services\Billing\FxRateService;
use App\Services\Billing\TenantBillingRegion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class MultiCurrencyBillingTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureFxSchema();
    }

    public function test_country_maps_to_currency(): void
    {
        $this->assertSame('PKR', TenantBillingRegion::currencyFromCountry('PK'));
        $this->assertSame('AED', TenantBillingRegion::currencyFromCountry('AE'));
        $this->assertSame('USD', TenantBillingRegion::currencyFromCountry('US'));
        $this->assertSame('USD', TenantBillingRegion::currencyFromCountry('GB'));
    }

    public function test_fx_convert_uses_stored_rate(): void
    {
        $fx = app(FxRateService::class);
        $fx->upsert('USD', 'AED', 3.67);
        $fx->upsert('USD', 'PKR', 280);

        $this->assertEqualsWithDelta(367.0, $fx->convert(100, 'USD', 'AED'), 0.01);
        $this->assertEqualsWithDelta(28000.0, $fx->convert(100, 'USD', 'PKR'), 0.5);
        $this->assertEqualsWithDelta(1.0, $fx->rate('USD', 'USD'), 0.0001);
    }

    public function test_metrics_normalize_mrr_to_base_currency(): void
    {
        $this->ensureMembershipsSchema();
        $fx = app(FxRateService::class);
        $fx->upsert('USD', 'PKR', 280);

        \App\Models\Central\TenantMembership::query()->create([
            'tenant_id'     => 1,
            'plan_id'       => 1,
            'status'        => 'active',
            'billing_cycle' => 'monthly',
            'amount'        => 28000,
            'currency'      => 'PKR',
            'start_date'    => now()->toDateString(),
            'end_date'      => now()->addMonth()->toDateString(),
        ]);

        \App\Models\Central\TenantMembership::query()->create([
            'tenant_id'     => 2,
            'plan_id'       => 1,
            'status'        => 'active',
            'billing_cycle' => 'monthly',
            'amount'        => 100,
            'currency'      => 'USD',
            'start_date'    => now()->toDateString(),
            'end_date'      => now()->addMonth()->toDateString(),
        ]);

        $snapshot = app(\App\Services\Billing\PlatformSubscriptionMetricsService::class)->snapshot();

        $this->assertSame('USD', $snapshot['base_currency']);
        $this->assertEqualsWithDelta(200.0, $snapshot['mrr_base'], 0.5);
        $this->assertSame(2, $snapshot['active_paid']);
    }

    public function test_billing_money_minor_units(): void
    {
        $this->assertSame(1050, BillingMoney::toMinorUnits(10.50, 'USD'));
        $this->assertSame(1050, BillingMoney::toMinorUnits(10.50, 'AED'));
        $this->assertSame(100, BillingMoney::toMinorUnits(100, 'JPY'));
    }

    public function test_stripe_portal_requires_customer(): void
    {
        $tenant = new Tenant([
            'name' => 'X',
            'stripe_customer_id' => null,
        ]);

        config(['services.stripe.secret' => 'sk_test_x']);
        $this->mock(\App\Services\Billing\PlatformBillingSettingsService::class, function ($mock) {
            $mock->shouldReceive('isReady')->with('stripe')->andReturn(true);
        });

        $this->expectException(\RuntimeException::class);
        app(\App\Services\Billing\TenantStripeCheckoutService::class)
            ->createBillingPortalUrl($tenant, 'https://example.com/billing');
    }

    private function ensureFxSchema(): void
    {
        if (! Schema::connection('central')->hasTable('platform_fx_rates')) {
            Schema::connection('central')->create('platform_fx_rates', function (Blueprint $table) {
                $table->id();
                $table->string('base_currency', 3);
                $table->string('quote_currency', 3);
                $table->decimal('rate', 18, 8);
                $table->timestamp('effective_at')->nullable();
                $table->string('source', 32)->default('manual');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['base_currency', 'quote_currency']);
            });
        }
    }

    private function ensureMembershipsSchema(): void
    {
        if (! Schema::connection('central')->hasTable('tenant_memberships')) {
            Schema::connection('central')->create('tenant_memberships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('status')->default('active');
                $table->string('billing_cycle')->default('monthly');
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }
    }
}
