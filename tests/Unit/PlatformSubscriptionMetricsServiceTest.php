<?php

namespace Tests\Unit;

use App\Services\Billing\PlatformSubscriptionMetricsService;
use Tests\TestCase;

class PlatformSubscriptionMetricsServiceTest extends TestCase
{
    public function test_monthly_equivalent_normalizes_yearly(): void
    {
        $service = app(PlatformSubscriptionMetricsService::class);

        $this->assertSame(100.0, $service->monthlyEquivalent(100, 'monthly'));
        $this->assertSame(10.0, $service->monthlyEquivalent(120, 'yearly'));
    }
}
