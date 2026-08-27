<?php

namespace Tests\Feature;

use App\Models\Central\PlatformStatusComponent;
use App\Models\Central\PlatformStatusIncident;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class StatusPageTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
    }

    public function test_public_status_page_loads_with_defaults(): void
    {
        $this->get(route('status.get'))
            ->assertOk()
            ->assertSee('Ledrix system status')
            ->assertSee('All systems operational');

        $this->assertSame(6, PlatformStatusComponent::query()->count());
    }

    public function test_open_incident_appears_on_status_page(): void
    {
        PlatformStatusComponent::ensureDefaults();

        PlatformStatusIncident::query()->create([
            'title'      => 'API latency elevated',
            'body'       => 'Investigating elevated response times.',
            'severity'   => 'minor',
            'status'     => 'investigating',
            'started_at' => now(),
        ]);

        $this->get(route('status.get'))
            ->assertOk()
            ->assertSee('API latency elevated')
            ->assertSee('Investigating elevated response times.');
    }

    public function test_status_subscribe_stores_email(): void
    {
        $this->from(route('status.get'))
            ->post(route('status.subscribe'), ['email' => 'ops@example.com'])
            ->assertRedirect(route('status.get'));

        $this->assertDatabaseHas('platform_status_subscribers', [
            'email' => 'ops@example.com',
        ], 'central');
    }
}
