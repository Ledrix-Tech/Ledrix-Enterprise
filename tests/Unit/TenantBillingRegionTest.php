<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Services\Billing\TenantBillingRegion;
use Tests\TestCase;

class TenantBillingRegionTest extends TestCase
{
    public function test_pakistan_and_uae_and_default_currencies(): void
    {
        $this->assertTrue(TenantBillingRegion::isPakistanCountry('PK'));
        $this->assertTrue(TenantBillingRegion::isPakistanCountry('pakistan'));
        $this->assertSame('PKR', TenantBillingRegion::currencyFromCountry('PK'));
        $this->assertSame('AED', TenantBillingRegion::currencyFromCountry('AE'));
        $this->assertSame('USD', TenantBillingRegion::currencyFromCountry('US'));
    }

    public function test_preferred_currency_wins_when_supported(): void
    {
        $tenant = new Tenant([
            'country' => 'PK',
            'preferred_billing_currency' => 'USD',
        ]);

        $this->assertSame('USD', TenantBillingRegion::currencyForTenant($tenant));
        $this->assertFalse(TenantBillingRegion::isPakistanBuyer($tenant));
        $this->assertTrue(TenantBillingRegion::usesStripe($tenant));
    }

    public function test_country_fallback_when_preferred_missing(): void
    {
        $tenant = new Tenant([
            'country' => 'PK',
            'preferred_billing_currency' => null,
        ]);

        $this->assertSame('PKR', TenantBillingRegion::currencyForTenant($tenant));
        $this->assertTrue(TenantBillingRegion::isPakistanBuyer($tenant));
    }

    public function test_aed_preferred_for_uae_tenant(): void
    {
        $tenant = new Tenant([
            'country' => 'AE',
            'preferred_billing_currency' => 'AED',
        ]);

        $this->assertSame('AED', TenantBillingRegion::currencyForTenant($tenant));
        $this->assertTrue(TenantBillingRegion::usesStripe($tenant));
        $this->assertStringContainsString('AED', TenantBillingRegion::regionLabel($tenant));
    }
}
