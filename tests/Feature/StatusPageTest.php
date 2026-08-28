<?php

namespace Tests\Feature;

use App\Models\Central\PlatformStatusComponent;
use App\Models\Central\PlatformStatusIncident;
use App\Models\Central\PlatformStatusSubscriber;
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

    public function test_status_unsubscribe_removes_subscriber(): void
    {
        $subscriber = PlatformStatusSubscriber::issue('ops@example.com');
        $subscriber->forceFill(['confirmed_at' => now()])->save();

        $this->get(route('status.unsubscribe', ['token' => $subscriber->token]))
            ->assertRedirect(route('status.get'));

        $this->assertDatabaseMissing('platform_status_subscribers', [
            'email' => 'ops@example.com',
        ], 'central');
    }

    public function test_publishing_an_incident_emails_confirmed_subscribers(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $subscriber = PlatformStatusSubscriber::issue('ops@example.com');
        $subscriber->forceFill(['confirmed_at' => now()])->save();

        $incident = PlatformStatusIncident::query()->create([
            'title'      => 'API latency elevated',
            'body'       => 'Investigating.',
            'severity'   => 'minor',
            'status'     => 'investigating',
            'started_at' => now(),
        ]);

        $sent = app(\App\Services\Platform\StatusIncidentNotifier::class)->notify($incident, 'published');

        $this->assertSame(1, $sent);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\StatusIncidentMail::class, 1);
    }
}
