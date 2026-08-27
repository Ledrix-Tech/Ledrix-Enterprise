<?php

namespace Tests\Feature;

use App\Models\Central\PackagePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class MarketingPagesSmokeTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();

        PackagePricing::query()->create([
            'name'          => 'Basic',
            'slug'          => 'basic',
            'monthly_price' => 19,
            'yearly_price'  => 190,
            'currency'      => 'USD',
            'trial_days'    => 7,
            'is_popular'    => false,
            'is_public'     => true,
            'sort_order'    => 1,
            'status'        => 'active',
        ]);

        PackagePricing::query()->create([
            'name'          => 'Premium',
            'slug'          => 'premium',
            'monthly_price' => 49,
            'yearly_price'  => 490,
            'currency'      => 'USD',
            'trial_days'    => 21,
            'is_popular'    => true,
            'is_public'     => true,
            'sort_order'    => 2,
            'status'        => 'active',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function marketingRoutesProvider(): array
    {
        return [
            'home'     => ['index.get'],
            'contact'  => ['contact-us.get'],
            'pricing'  => ['pricing.get'],
            'features' => ['features.get'],
            'about'    => ['about.get'],
            'faq'      => ['faq.get'],
            'terms'    => ['terms.get'],
            'privacy'  => ['privacy.get'],
        ];
    }

    /** @dataProvider marketingRoutesProvider */
    public function test_marketing_page_loads_without_error(string $routeName): void
    {
        $this->get(route($routeName))->assertOk();
    }
}
