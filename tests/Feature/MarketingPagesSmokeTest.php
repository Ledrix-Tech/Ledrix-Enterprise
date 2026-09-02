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
            'security' => ['security.get'],
        ];
    }

    /** @dataProvider marketingRoutesProvider */
    public function test_marketing_page_loads_without_error(string $routeName): void
    {
        $this->get(route($routeName))->assertOk();
    }

    public function test_homepage_keeps_role_and_status_sections_off_compliance(): void
    {
        $this->get(route('index.get'))
            ->assertOk()
            ->assertSee('Every role gets its own view', false)
            ->assertSee('They don’t have to ask — they can check', false)
            ->assertDontSee('You’ll know before they have to tell you', false)
            ->assertDontSee('Is Ledrix GDPR compliant', false)
            ->assertDontSee('custom domain', false);
    }

    public function test_features_page_uses_categories_and_rbac_table(): void
    {
        $this->get(route('features.get'))
            ->assertOk()
            ->assertSee('Multi-brand / multi-LLC', false)
            ->assertSee('Agency &amp; branding', false)
            ->assertSee('White-label the client portal', false)
            ->assertSee('Who sees what', false)
            ->assertSee('payout reports', false)
            ->assertSee('Integrations', false)
            ->assertSee('API tokens', false)
            ->assertSee('Disputes &amp; refunds', false)
            ->assertSee(route('security.get', [], false), false);
    }

    public function test_security_page_is_factual_and_linked_from_faq(): void
    {
        $this->get(route('security.get'))
            ->assertOk()
            ->assertSee('Workspace isolation', false)
            ->assertSee('SSO and SCIM', false)
            ->assertSee('We do not claim one', false)
            ->assertDontSee('SOC 2 certified', false);

        $this->get(route('faq.get'))
            ->assertOk()
            ->assertSee('Is Ledrix GDPR compliant?', false)
            ->assertSee('Can I use my own domain for my client portal?', false)
            ->assertSee('Can I send website leads into Ledrix with a script or API?', false)
            ->assertSee('Does Ledrix support SSO or SCIM?', false)
            ->assertSee(route('security.get', [], false), false);
    }
}
