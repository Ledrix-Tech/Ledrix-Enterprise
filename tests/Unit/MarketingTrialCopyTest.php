<?php

namespace Tests\Unit;

use App\Models\Central\PackagePricing;
use App\Support\MarketingTrialCopy;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MarketingTrialCopyTest extends TestCase
{
    public function test_label_uses_package_specific_trial_days(): void
    {
        $basic = new PackagePricing(['name' => 'Basic', 'trial_days' => 7]);
        $premium = new PackagePricing(['name' => 'Premium', 'trial_days' => 21]);

        $this->assertSame('7-day free trial', MarketingTrialCopy::label($basic));
        $this->assertSame('21-day free trial', MarketingTrialCopy::label($premium));
    }

    public function test_generic_label_avoids_max_trial_when_plans_differ(): void
    {
        $packages = collect([
            new PackagePricing(['trial_days' => 7]),
            new PackagePricing(['trial_days' => 21]),
        ]);

        $this->assertSame('plan-based free trial', MarketingTrialCopy::genericLabel($packages));
        $this->assertSame('7–21-day free trials', MarketingTrialCopy::rangeLabel($packages));
    }

    public function test_generic_label_uses_shared_days_when_all_plans_match(): void
    {
        $packages = collect([
            new PackagePricing(['trial_days' => 14]),
            new PackagePricing(['trial_days' => 14]),
        ]);

        $this->assertSame('14-day free trial', MarketingTrialCopy::genericLabel($packages));
    }

    public function test_start_generic_cta_uses_plan_based_wording_when_trials_differ(): void
    {
        $packages = collect([
            new PackagePricing(['trial_days' => 7]),
            new PackagePricing(['trial_days' => 21]),
        ]);

        $this->assertSame('Start free trial', MarketingTrialCopy::startGenericCta($packages));
    }

    public function test_start_generic_cta_includes_days_when_all_plans_match(): void
    {
        $packages = collect([
            new PackagePricing(['trial_days' => 14]),
            new PackagePricing(['trial_days' => 14]),
        ]);

        $this->assertSame('Start 14-day free trial', MarketingTrialCopy::startGenericCta($packages));
    }
}
