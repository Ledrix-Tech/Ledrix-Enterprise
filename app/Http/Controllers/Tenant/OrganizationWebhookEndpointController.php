<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\TenantWebhookEndpoint;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrganizationWebhookEndpointController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('api_access', (int) $tenant->id);

        $endpoints = TenantWebhookEndpoint::query()
            ->where('tenant_id', $tenant->id)
            ->withCount('deliveries')
            ->latest()
            ->get();

        return $this->organizationView('webhooks', [
            'tenant'          => $tenant,
            'endpoints'       => $endpoints,
            'availableEvents' => TenantWebhookEndpoint::AVAILABLE_EVENTS,
        ]);
    }

    public function store(Request $request, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('api_access', (int) $tenant->id);

        $urlRules = ['required', 'url', 'max:500'];
        if (app()->environment('production')) {
            $urlRules[] = 'starts_with:https://';
        }

        $validated = $request->validate([
            'name'     => ['nullable', 'string', 'max:100'],
            'url'      => $urlRules,
            'events'   => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in(TenantWebhookEndpoint::AVAILABLE_EVENTS)],
        ]);

        $secret = TenantWebhookEndpoint::generateSecret();

        $endpoint = TenantWebhookEndpoint::query()->create([
            'tenant_id' => $tenant->id,
            'name'      => $validated['name'] ?? 'Webhook',
            'url'       => $validated['url'],
            'secret'    => $secret,
            'events'    => array_values($validated['events']),
            'enabled'   => true,
        ]);

        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user();
        AuditLog::record(
            'tenant.webhook_endpoint_created',
            (int) $tenant->id,
            Auth::guard('admin')->check() ? 'admin' : 'tenant',
            $actor?->id,
            $actor?->name ?? $tenant->name,
            [
                'subject_type' => 'tenant_webhook_endpoint',
                'subject_id'   => $endpoint->id,
                'description'  => 'Outbound webhook endpoint created',
            ]
        );

        return $this->organizationRedirect('webhooks', [], 'success', 'Webhook endpoint created. Copy the signing secret now.')
            ->with('new_webhook_secret', $secret);
    }

    public function toggle(int $id, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('api_access', (int) $tenant->id);

        $endpoint = TenantWebhookEndpoint::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $endpoint->forceFill(['enabled' => ! $endpoint->enabled])->save();

        return $this->organizationRedirect(
            'webhooks',
            [],
            'success',
            $endpoint->enabled ? 'Webhook enabled.' : 'Webhook disabled.'
        );
    }

    public function destroy(int $id, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('api_access', (int) $tenant->id);

        $endpoint = TenantWebhookEndpoint::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $endpoint->delete();

        return $this->organizationRedirect('webhooks', [], 'success', 'Webhook endpoint removed.');
    }
}
