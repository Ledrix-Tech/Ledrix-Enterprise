<?php

namespace Tests\Feature;

use App\Models\Central\PackagePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantRegistrationSuccessTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();

        PackagePricing::query()->create([
            'name'          => 'CRM Basic',
            'slug'          => 'crm-basic',
            'monthly_price' => 29,
            'yearly_price'  => 290,
            'currency'      => 'USD',
            'trial_days'    => 7,
            'is_public'     => true,
            'status'        => 'active',
        ]);
    }

    public function test_register_form_renders_for_public_plan(): void
    {
        $this->get(route('tenant.register.form', 'crm-basic'))
            ->assertOk()
            ->assertSee('7-day free trial')
            ->assertSee('Start 7-day free trial')
            ->assertSee('data-country-picker', false)
            ->assertSee('Pakistan', false)
            ->assertSee('United Arab Emirates', false)
            ->assertSee('Germany', false);
    }

    public function test_register_success_url_does_not_404_as_plan_slug(): void
    {
        $response = $this->get('/register/success');

        $response->assertRedirect(route('pricing.get'));
        $response->assertStatus(302);
    }
}
