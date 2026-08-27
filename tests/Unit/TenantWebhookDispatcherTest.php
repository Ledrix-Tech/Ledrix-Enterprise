<?php

namespace Tests\Unit;

use App\Jobs\DispatchTenantWebhookJob;
use App\Models\Central\Tenant;
use App\Models\Central\TenantWebhookDelivery;
use App\Models\Central\TenantWebhookEndpoint;
use App\Services\Tenant\TenantWebhookDispatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantWebhookDispatcherTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
    }

    public function test_dispatch_queues_job_for_matching_endpoint(): void
    {
        Queue::fake();

        $tenant = Tenant::query()->create([
            'name'     => 'Hook Co',
            'slug'     => 'hook-co',
            'email'    => 'hook@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        TenantWebhookEndpoint::query()->create([
            'tenant_id' => $tenant->id,
            'name'      => 'Billing',
            'url'       => 'https://example.com/hooks',
            'secret'    => 'whsec_test',
            'events'    => ['invoice.paid'],
            'enabled'   => true,
        ]);

        $deliveries = app(TenantWebhookDispatcher::class)->dispatch((int) $tenant->id, 'invoice.paid', [
            'data' => ['ok' => true],
        ]);

        $this->assertCount(1, $deliveries);
        Queue::assertPushed(DispatchTenantWebhookJob::class);
        $this->assertSame('pending', $deliveries[0]->status);
    }

    public function test_job_posts_signed_payload_and_marks_delivered(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $tenant = Tenant::query()->create([
            'name'     => 'Hook Co 2',
            'slug'     => 'hook-co-2',
            'email'    => 'hook2@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $endpoint = TenantWebhookEndpoint::query()->create([
            'tenant_id' => $tenant->id,
            'url'       => 'https://example.com/hooks',
            'secret'    => 'whsec_abc',
            'events'    => ['membership.activated'],
            'enabled'   => true,
        ]);

        $delivery = TenantWebhookDelivery::query()->create([
            'tenant_id'   => $tenant->id,
            'endpoint_id' => $endpoint->id,
            'event'       => 'membership.activated',
            'payload'     => ['event' => 'membership.activated', 'data' => ['x' => 1]],
            'status'      => 'pending',
            'attempts'    => 0,
        ]);

        (new DispatchTenantWebhookJob($delivery->id))->handle();

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(200, $delivery->response_code);
        $this->assertNotNull($delivery->delivered_at);

        Http::assertSent(function ($request) use ($endpoint, $delivery) {
            $body = $request->body();
            $sig = $request->header('X-Ledrix-Signature')[0] ?? '';
            $expected = 'sha256='.hash_hmac('sha256', $body, $endpoint->secret);

            return $request->url() === 'https://example.com/hooks'
                && $sig === $expected
                && ($request->header('X-Ledrix-Event')[0] ?? '') === 'membership.activated'
                && ($request->header('X-Ledrix-Delivery')[0] ?? '') === (string) $delivery->id;
        });
    }
}
