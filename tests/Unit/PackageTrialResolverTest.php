<?php

namespace Tests\Unit;

use App\Models\Central\PackagePricing;
use App\Services\Billing\PackageTrialResolver;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PackageTrialResolverTest extends TestCase
{
    public function test_resolves_seven_day_trial_for_basic_plan(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');

        $package = new PackagePricing(['trial_days' => 7]);
        $trial = (new PackageTrialResolver())->resolve($package);

        $this->assertSame(7, $trial['trial_days']);
        $this->assertSame('trialing', $trial['membership_status']);
        $this->assertSame('2026-09-01', $trial['trial_end']);
        $this->assertSame('2026-09-01', $trial['end_date']);
        $this->assertTrue($trial['trial_used']);
        $this->assertNotNull($trial['trial_ends_at']);

        Carbon::setTestNow();
    }

    public function test_resolves_no_trial_when_days_zero(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');

        $package = new PackagePricing(['trial_days' => 0]);
        $trial = (new PackageTrialResolver())->resolve($package);

        $this->assertSame(0, $trial['trial_days']);
        $this->assertSame('past_due', $trial['membership_status']);
        $this->assertNull($trial['trial_start']);
        $this->assertNull($trial['trial_end']);
        $this->assertSame('2026-08-25', $trial['end_date']);
        $this->assertFalse($trial['trial_used']);
        $this->assertNull($trial['trial_ends_at']);

        Carbon::setTestNow();
    }
}
