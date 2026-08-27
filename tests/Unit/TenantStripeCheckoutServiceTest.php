<?php

namespace Tests\Unit;

use App\Services\Billing\TenantStripeCheckoutService;
use App\Services\Billing\PlatformBillingSettingsService;
use Tests\TestCase;

class TenantStripeCheckoutServiceTest extends TestCase
{
    public function test_reports_not_configured_when_stripe_not_ready(): void
    {
        $this->mock(PlatformBillingSettingsService::class, function ($mock) {
            $mock->shouldReceive('isReady')->with('stripe')->andReturn(false);
        });

        $this->assertFalse(app(TenantStripeCheckoutService::class)->isConfigured());
    }
}
