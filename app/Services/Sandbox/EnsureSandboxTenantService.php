<?php

namespace App\Services\Sandbox;

use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantFeatureOverride;
use App\Models\Central\TenantLimitOverride;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantUsageSnapshot;
use App\Support\SandboxDatabase;
use App\Support\SandboxWorkspace;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnsureSandboxTenantService
{
    public function ensure(): Tenant
    {
        $slug = SandboxWorkspace::slug();
        $tenant = Tenant::withTrashed()->where('slug', $slug)->first();

        if ($tenant?->trashed()) {
            $tenant->restore();
        }

        $package = $this->resolvePackage();

        if (! $tenant) {
            $tenant = Tenant::query()->create(SandboxWorkspace::fillableOn('tenants', [
                'plan_id'            => $package->id,
                'name'               => (string) config('sandbox.tenant_name', 'Ledrix Demo Workspace'),
                'slug'               => $slug,
                'email'              => SandboxWorkspace::tenantEmail(),
                'password'           => Hash::make(Str::password(32)),
                'country'            => 'US',
                'status'             => 'active',
                'trial_used'         => false,
                'trial_ends_at'      => null,
                'email_verified_at'  => now(),
                'registered_ip'      => request()?->ip(),
                'meta'               => [
                    'is_sandbox'      => true,
                    'registered_from' => 'sandbox',
                    'crm_connection'  => SandboxDatabase::connectionName(),
                ],
                'crm_database'       => SandboxDatabase::databaseName(),
            ]));
        } else {
            $meta = is_array($tenant->meta) ? $tenant->meta : [];
            $meta['is_sandbox'] = true;
            $meta['crm_connection'] = SandboxDatabase::connectionName();
            $tenant->fill(SandboxWorkspace::fillableOn('tenants', [
                'plan_id'           => $package->id,
                'name'              => (string) config('sandbox.tenant_name', $tenant->name),
                'status'            => 'active',
                'trial_used'        => false,
                'trial_ends_at'     => null,
                'email_verified_at' => $tenant->email_verified_at ?? now(),
                'suspended_reason'  => null,
                'suspended_at'      => null,
                'crm_database'      => SandboxDatabase::databaseName(),
                'meta'              => $meta,
            ]))->save();
        }

        $this->ensureMembership($tenant->fresh(), $package);
        $this->ensureUsageSnapshot($tenant);
        $this->ensureFeatureOverride($tenant);
        $this->ensureLimitOverride($tenant);

        return $tenant->fresh(['plan', 'activeMembership']);
    }

    private function resolvePackage(): PackagePricing
    {
        $slug = (string) config('sandbox.plan_slug', 'ledrix-sandbox-tour');
        $package = PackagePricing::query()->where('slug', $slug)->first();

        if ($package) {
            return $package;
        }

        $popular = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderByDesc('is_popular')
            ->orderBy('sort_order')
            ->first();

        if ($popular) {
            return $popular;
        }

        return PackagePricing::query()->create(SandboxWorkspace::fillableOn('package_pricings', [
            'name'                        => 'Sandbox Tour',
            'slug'                        => $slug,
            'description'                 => 'Internal shared sandbox plan. Not sold.',
            'monthly_price'               => 0,
            'yearly_price'                => 0,
            'currency'                    => 'USD',
            'trial_days'                  => 0,
            'is_popular'                  => false,
            'is_public'                   => false,
            'sort_order'                  => 99,
            'status'                      => 'active',
            'max_brands'                  => 50,
            'max_sellers'                 => 50,
            'max_admins'                  => 10,
            'max_clients'                 => 100,
            'max_leads_per_month'         => 10000,
            'max_orders'                  => 10000,
            'max_payment_links'           => 10000,
            'max_account_keys'            => 50,
            'max_projects'                => 50,
            'feature_ppc_module'          => true,
            'feature_stripe'              => true,
            'feature_paypal'              => true,
            'feature_webhooks'            => true,
            'feature_chargeback_tracking' => true,
            'feature_client_portal'       => true,
            'feature_lead_prediction'     => true,
            'feature_seller_leaderboard'  => true,
            'feature_support_tickets'     => true,
            'feature_projects'            => true,
            'feature_milestone_payments'  => true,
            'feature_api_access'          => false,
            'feature_custom_domain'       => false,
            'feature_white_label'         => false,
        ]));
    }

    private function ensureMembership(Tenant $tenant, PackagePricing $package): void
    {
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->latest('start_date')
            ->first();

        $payload = SandboxWorkspace::fillableOn('tenant_memberships', [
            'tenant_id'          => $tenant->id,
            'plan_id'            => $package->id,
            'billing_cycle'      => 'yearly',
            'amount'             => 0,
            'currency'           => 'USD',
            'api_key'            => $membership?->api_key ?: $this->uniqueApiKey(),
            'start_date'         => now()->toDateString(),
            'end_date'           => now()->addYears(10)->toDateString(),
            'trial_start'        => null,
            'trial_end'          => null,
            'status'             => 'active',
            'renewed_by'         => 'super_admin',
            'conversion_source'  => 'sandbox',
            'cancelled_at'       => null,
            'meta'               => ['is_sandbox' => true],
        ]);

        if ($membership) {
            unset($payload['api_key']);
            $membership->fill($payload)->save();

            return;
        }

        TenantMembership::query()->create($payload);
    }

    private function ensureUsageSnapshot(Tenant $tenant): void
    {
        if (TenantUsageSnapshot::query()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        TenantUsageSnapshot::query()->create(SandboxWorkspace::fillableOn('tenant_usage_snapshots', [
            'tenant_id'      => $tenant->id,
            'month_reset_at' => now()->startOfMonth(),
        ]));
    }

    private function ensureFeatureOverride(Tenant $tenant): void
    {
        $payload = SandboxWorkspace::fillableOn('tenant_feature_overrides', [
            'tenant_id'                   => $tenant->id,
            'feature_ppc_module'          => true,
            'feature_upwork_module'       => false,
            'feature_milestone_payments'  => true,
            'feature_stripe'              => true,
            'feature_paypal'              => true,
            'feature_webhooks'            => true,
            'feature_chargeback_tracking' => true,
            'feature_dual_invoicing'      => true,
            'feature_client_portal'       => true,
            'feature_lead_prediction'     => true,
            'feature_seller_leaderboard'  => true,
            'feature_performance_bonus'   => true,
            'feature_projects'            => true,
            'feature_support_tickets'     => true,
            'feature_api_access'          => false,
            'feature_custom_domain'       => false,
            'feature_white_label'         => false,
            'override_reason'             => 'Shared Ledrix sandbox tour',
            'expires_at'                  => null,
        ]);

        TenantFeatureOverride::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            $payload
        );
    }

    private function ensureLimitOverride(Tenant $tenant): void
    {
        $payload = SandboxWorkspace::fillableOn('tenant_limit_overrides', [
            'tenant_id'           => $tenant->id,
            'max_brands'          => 50,
            'max_sellers'         => 50,
            'max_admins'          => 10,
            'max_clients'         => 100,
            'max_leads_per_month' => 10000,
            'max_orders'          => 10000,
            'max_payment_links'   => 10000,
            'max_account_keys'    => 50,
            'max_projects'        => 50,
            'max_storage_mb'      => 5000,
            'override_reason'     => 'Shared Ledrix sandbox tour',
            'expires_at'          => null,
        ]);

        TenantLimitOverride::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            $payload
        );
    }

    private function uniqueApiKey(): string
    {
        do {
            $key = Str::random(64);
        } while (TenantMembership::query()->where('api_key', $key)->exists());

        return $key;
    }
}
