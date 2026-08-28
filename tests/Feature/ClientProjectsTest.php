<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesPaymentFlow;
use Tests\TestCase;

/**
 * @group client
 */
class ClientProjectsTest extends TestCase
{
    use CreatesPaymentFlow;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('assertEnabled')->andReturnNull();
            $mock->shouldReceive('assertAnyEnabled')->andReturnNull();
        });

        $this->mock(SubscriptionAccessService::class, function ($mock) {
            $mock->shouldReceive('canUseCrm')->andReturn(true);
        });
    }

    public function test_client_sees_own_project_status_and_progress(): void
    {
        [$client, $order, $project] = $this->seedClientProject();

        ProjectTask::query()->create([
            'tenant_id'  => 1,
            'project_id' => $project->id,
            'title'      => 'Wireframes',
            'status'     => 'completed',
            'priority'   => 'medium',
        ]);
        ProjectTask::query()->create([
            'tenant_id'  => 1,
            'project_id' => $project->id,
            'title'      => 'Homepage build',
            'status'     => 'in_progress',
            'priority'   => 'high',
        ]);

        $this->actingAs($client, 'client')
            ->get(route('client.projects.index'))
            ->assertOk()
            ->assertSee('Website rebuild', false)
            ->assertSee('50%', false)
            ->assertSee('In Progress', false);

        $this->actingAs($client, 'client')
            ->get(route('client.projects.show', $project))
            ->assertOk()
            ->assertSee('Website rebuild', false)
            ->assertSee('Wireframes', false)
            ->assertSee('Homepage build', false)
            ->assertSee('1 of 2 tasks complete', false)
            ->assertSee('Paid', false)
            ->assertSee('In delivery', false)
            ->assertDontSee('assigned_to', false);
    }

    public function test_client_cannot_view_another_clients_project(): void
    {
        [$owner] = $this->seedClientProject('owner@example.com');
        [, , $otherProject] = $this->seedClientProject('other@example.com');

        $this->actingAs($owner, 'client')
            ->get(route('client.projects.show', $otherProject))
            ->assertForbidden();
    }

    public function test_paid_order_without_project_shows_as_waiting_to_start(): void
    {
        $client = $this->createPortalClient();

        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $client->id],
        ]);

        Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Logo Design',
            'currency'        => 'USD',
            'unit_amount'     => 10_000,
            'amount_paid'     => 10_000,
            'status'          => 'paid',
            'paid_at'         => now(),
            'order_type'      => 'original',
        ]);

        $this->actingAs($client, 'client')
            ->get(route('client.projects.index'))
            ->assertOk()
            ->assertSee('Logo Design', false)
            ->assertSee('Kickoff pending', false);
    }

    public function test_invoice_details_link_to_project_when_present(): void
    {
        [$client, $order, $project] = $this->seedClientProject();

        $this->actingAs($client, 'client')
            ->get(route('client.invoice.details', $order))
            ->assertOk()
            ->assertSee(route('client.projects.show', $project), false)
            ->assertSee('View project progress', false);
    }

    /**
     * @return array{0: Client, 1: Order, 2: Project}
     */
    private function seedClientProject(string $email = 'client-projects@example.com'): array
    {
        $client = $this->createPortalClient(['email' => $email]);

        ['brand' => $brand, 'seller' => $seller, 'lead' => $lead] = $this->createPaymentLeadGraph([
            'lead' => ['client_id' => $client->id],
        ]);

        $order = Order::create([
            'tenant_id'       => 1,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => 'Website rebuild',
            'currency'        => 'USD',
            'unit_amount'     => 50_000,
            'amount_paid'     => 50_000,
            'status'          => 'in_progress',
            'paid_at'         => now(),
            'order_type'      => 'original',
        ]);

        $project = Project::query()->create([
            'tenant_id'       => 1,
            'title'           => 'Website rebuild',
            'lead_id'         => $lead->id,
            'order_id'        => $order->id,
            'front_seller_id' => $seller->id,
            'owner_seller_id' => $seller->id,
            'status'          => 'in_progress',
            'start_date'      => now()->toDateString(),
            'due_date'        => now()->addWeeks(3)->toDateString(),
            'description'     => 'Client-visible delivery work',
        ]);

        return [$client, $order, $project];
    }

    private function createPortalClient(array $attributes = []): Client
    {
        return Client::factory()->create(array_merge([
            'tenant_id' => 1,
            'status'    => 'Active',
            'password'  => 'password123',
            'meta'      => ['portal_access' => true],
        ], $attributes));
    }
}
