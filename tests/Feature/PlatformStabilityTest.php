<?php

namespace Tests\Feature;

use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * F-17 (focused, non-billing): platform routes stay healthy after F-18 cleanup.
 */
class PlatformStabilityTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
    }

    public function test_half_dead_sanctum_client_api_routes_are_gone(): void
    {
        $this->getJson('/api/client/briefs')->assertNotFound();
        $this->getJson('/api/client/profile')->assertNotFound();
        $this->postJson('/api/client/profile-update')->assertNotFound();
    }

    public function test_tenant_management_api_and_scim_still_registered(): void
    {
        $this->getJson('/api/v1/company')->assertUnauthorized();
        config(['scim.enabled' => false]);
        $this->getJson('/api/scim/v2/Users')->assertNotFound();
    }

    public function test_public_status_and_seller_assignment_route_exist(): void
    {
        $this->get(route('status.get'))->assertOk();

        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('seller.assignment.update-status')
        );
    }

    public function test_marketing_home_loads(): void
    {
        $this->get(route('index.get'))->assertOk();
    }
}
