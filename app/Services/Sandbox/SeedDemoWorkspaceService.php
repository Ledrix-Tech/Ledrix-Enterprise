<?php

namespace App\Services\Sandbox;

use App\Models\Demos\AccountKey;
use App\Models\Demos\Admin;
use App\Models\Demos\Brand;
use App\Models\Demos\Client;
use App\Models\Demos\Lead;
use App\Models\Demos\LeadAssignment;
use App\Models\Demos\Order;
use App\Models\Demos\Payment;
use App\Models\Demos\Seller;
use App\Models\Central\Tenant;
use App\Support\SandboxDatabase;
use App\Support\SandboxWorkspace;
use App\Support\TenantContext;
use Illuminate\Support\Str;

class SeedDemoWorkspaceService
{
    public function seed(Tenant $tenant, bool $force = false): void
    {
        SandboxDatabase::using(function () use ($tenant, $force) {
            $this->seedOnDemosConnection($tenant, $force);
        });
    }

    private function seedOnDemosConnection(Tenant $tenant, bool $force): void
    {
        $previous = TenantContext::resolve();
        TenantContext::set((int) $tenant->id);

        try {
            $atlasName = (string) config('sandbox.brands.atlas', 'Demo Atlas');
            $already = Brand::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('brand_name', $atlasName)
                ->exists();

            if ($already && ! $force) {
                $this->ensureActors($tenant);

                return;
            }

            $atlas = $this->upsertBrand($tenant, $atlasName, 'https://demo-atlas.example');
            $northstar = $this->upsertBrand(
                $tenant,
                (string) config('sandbox.brands.northstar', 'Demo Northstar'),
                'https://demo-northstar.example'
            );

            $this->upsertAccountKey($tenant, $atlas, 'atlas');
            $this->upsertAccountKey($tenant, $northstar, 'northstar');

            $this->ensureAdmin($tenant);
            $closer = $this->ensureSeller($tenant, $atlas, (string) config('sandbox.seller_email'), 'Demo Closer', 'front_seller');
            $pm = $this->ensureSeller($tenant, $northstar, (string) config('sandbox.pm_email'), 'Demo Project Manager', 'project_manager');
            $client = $this->ensureClient($tenant);

            $this->seedLeadsAndPayments($tenant, $atlas, $northstar, $closer, $pm, $client);
        } finally {
            $previous ? TenantContext::set($previous) : TenantContext::clear();
        }
    }

    private function ensureActors(Tenant $tenant): void
    {
        $atlas = Brand::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('brand_name', (string) config('sandbox.brands.atlas', 'Demo Atlas'))
            ->first();

        $northstar = Brand::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('brand_name', (string) config('sandbox.brands.northstar', 'Demo Northstar'))
            ->first();

        $this->ensureAdmin($tenant);

        if ($atlas) {
            $this->ensureSeller($tenant, $atlas, (string) config('sandbox.seller_email'), 'Demo Closer', 'front_seller');
        }
        if ($northstar) {
            $this->ensureSeller($tenant, $northstar, (string) config('sandbox.pm_email'), 'Demo Project Manager', 'project_manager');
        }

        $this->ensureClient($tenant);
    }

    private function upsertBrand(Tenant $tenant, string $name, string $url): Brand
    {
        $brand = Brand::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('brand_name', $name)
            ->first();

        if ($brand) {
            return $brand;
        }

        return Brand::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'module'     => 'ppc',
            'brand_name' => $name,
            'brand_url'  => $url,
            'status'     => 'Active',
        ]);
    }

    private function upsertAccountKey(Tenant $tenant, Brand $brand, string $suffix): void
    {
        $exists = AccountKey::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('brand_id', $brand->id)
            ->exists();

        if ($exists) {
            return;
        }

        AccountKey::withoutGlobalScopes()->create([
            'tenant_id'             => $tenant->id,
            'brand_id'              => $brand->id,
            'module'                => 'ppc',
            'status'                => 'active',
            'stripe_publishable_key'=> 'pk_test_sandbox_'.$suffix,
            'stripe_secret_key'     => 'sk_test_sandbox_'.$suffix,
            'paypal_client_id'      => 'paypal_sandbox_'.$suffix,
            'paypal_secret'         => 'paypal_sandbox_secret_'.$suffix,
        ]);
    }

    private function ensureAdmin(Tenant $tenant): Admin
    {
        $email = (string) config('sandbox.admin_email');

        $admin = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        if ($admin) {
            $this->clearTwoFactor($admin);

            return $admin->fresh();
        }

        return Admin::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name'      => 'Demo Workspace Admin',
            'email'     => $email,
            'password'  => Str::password(32),
            'role'      => 'admin',
        ]);
    }

    private function ensureSeller(Tenant $tenant, Brand $brand, string $email, string $name, string $role): Seller
    {
        $seller = Seller::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        if ($seller) {
            if ((int) $seller->brand_id !== (int) $brand->id) {
                $seller->update(['brand_id' => $brand->id]);
            }

            $this->clearTwoFactor($seller);

            return $seller->fresh();
        }

        return Seller::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'brand_id'  => $brand->id,
            'name'      => $name,
            'sudo_name' => $name,
            'email'     => $email,
            'password'  => Str::password(32),
            'is_seller' => $role,
            'status'    => 'Active',
        ]);
    }

    private function ensureClient(Tenant $tenant): Client
    {
        $email = (string) config('sandbox.client_email');

        $client = Client::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        if ($client) {
            $meta = is_array($client->meta) ? $client->meta : [];
            $meta['portal_access'] = true;
            $client->update([
                'status' => 'Active',
                'meta'   => $meta,
            ]);
            $this->clearTwoFactor($client);

            return $client->fresh();
        }

        return Client::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name'      => 'Demo Client',
            'email'     => $email,
            'password'  => Str::password(32),
            'phone'     => '+15555550100',
            'status'    => 'Active',
            'meta'      => ['portal_access' => true],
        ]);
    }

    private function clearTwoFactor(Admin|Seller|Client $user): void
    {
        if (! $user->two_factor_secret && ! $user->two_factor_recovery_codes) {
            return;
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    private function seedLeadsAndPayments(
        Tenant $tenant,
        Brand $atlas,
        Brand $northstar,
        Seller $closer,
        Seller $pm,
        Client $client,
    ): void {
        $newLead = $this->upsertLead($tenant, $atlas, $closer, $client, 'Maya Chen', 'maya.chen@example.com', 'new', 'PPC Management');
        $qualified = $this->upsertLead($tenant, $atlas, $closer, $client, 'Jordan Blake', 'jordan.blake@example.com', 'qualified', 'Website Rebuild');
        $won = $this->upsertLead($tenant, $atlas, $closer, $client, 'Riley Patel', 'riley.patel@example.com', 'completed', 'SEO Retainer');
        $disputed = $this->upsertLead($tenant, $northstar, $pm, $client, 'Sam Okonkwo', 'sam.okonkwo@example.com', 'completed', 'Content Engine');

        $this->assignLead($tenant, $newLead, $closer);
        $this->assignLead($tenant, $qualified, $closer);
        $this->assignLead($tenant, $won, $closer);
        $this->assignLead($tenant, $disputed, $pm);

        $paidOrder = $this->upsertOrder($tenant, $atlas, $closer, $client, $won, 'SEO Retainer', 250000, 'paid');
        $this->upsertPayment($tenant, $paidOrder, $closer, 'pi_sandbox_atlas_paid', 'succeeded', 'none', null);

        $lostOrder = $this->upsertOrder($tenant, $northstar, $pm, $client, $disputed, 'Content Engine', 89000, 'paid');
        $this->upsertPayment($tenant, $lostOrder, $pm, 'pi_sandbox_northstar_lost', 'succeeded', 'chargeback', 'lost');
    }

    private function upsertLead(
        Tenant $tenant,
        Brand $brand,
        Seller $seller,
        Client $client,
        string $name,
        string $email,
        string $status,
        string $service,
    ): Lead {
        $lead = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        $payload = [
            'tenant_id' => $tenant->id,
            'brand_id'  => $brand->id,
            'seller_id' => $seller->id,
            'client_id' => $client->id,
            'name'      => $name,
            'email'     => $email,
            'phone'     => '+15555550111',
            'service'   => $service,
            'status'    => $status,
            'source'    => 'sandbox',
            'message'   => 'Sandbox sample lead — not a real enquiry.',
        ];

        if ($status === 'completed') {
            $payload['converted_at'] = now()->subDays(12);
        }

        if ($lead) {
            $lead->fill($payload)->save();

            return $lead->fresh();
        }

        return Lead::withoutGlobalScopes()->create($payload);
    }

    private function assignLead(Tenant $tenant, Lead $lead, Seller $seller): void
    {
        $exists = LeadAssignment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('lead_id', $lead->id)
            ->where('assigned_to', $seller->id)
            ->exists();

        if ($exists) {
            return;
        }

        LeadAssignment::withoutGlobalScopes()->create([
            'tenant_id'     => $tenant->id,
            'lead_id'       => $lead->id,
            'assigned_to'   => $seller->id,
            'assigned_role' => $seller->is_seller,
            'assigned_by'   => $seller->id,
            'assigned_at'   => now()->subDays(3),
            'status'        => 'assigned',
        ]);
    }

    private function upsertOrder(
        Tenant $tenant,
        Brand $brand,
        Seller $seller,
        Client $client,
        Lead $lead,
        string $service,
        int $cents,
        string $status,
    ): Order {
        $order = Order::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('lead_id', $lead->id)
            ->where('service_name', $service)
            ->first();

        $payload = [
            'tenant_id'       => $tenant->id,
            'lead_id'         => $lead->id,
            'brand_id'        => $brand->id,
            'seller_id'       => $seller->id,
            'owner_seller_id' => $seller->id,
            'client_id'       => $client->id,
            'service_name'    => $service,
            'currency'        => 'USD',
            'unit_amount'     => $cents,
            'amount_paid'     => $cents,
            'balance_due'     => 0,
            'status'          => $status,
            'refund_status'   => 'none',
            'paid_at'         => now()->subDays(8),
            'buyer_name'      => $client->name,
            'buyer_email'     => $client->email,
        ];

        if ($order) {
            $order->fill($payload)->save();

            return $order->fresh();
        }

        return Order::withoutGlobalScopes()->create($payload);
    }

    private function upsertPayment(
        Tenant $tenant,
        Order $order,
        Seller $seller,
        string $intent,
        string $status,
        string $refundStatus,
        ?string $disputeStatus,
    ): void {
        $payment = Payment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('provider_payment_intent_id', $intent)
            ->first();

        $payload = [
            'tenant_id'                  => $tenant->id,
            'order_id'                   => $order->id,
            'seller_id'                  => $seller->id,
            'owner_seller_id'            => $seller->id,
            'credit_to_seller_id'        => $seller->id,
            'credited_seller_id'         => $seller->id,
            'amount'                     => $order->unit_amount,
            'currency'                   => 'USD',
            'status'                     => $status,
            'provider'                   => 'stripe',
            'provider_payment_intent_id' => $intent,
            'refund_status'              => $refundStatus,
            'refunded_amount'            => 0,
            'dispute_status'             => $disputeStatus,
            'paid_at'                    => now()->subDays(8),
            'source'                     => 'sandbox',
        ];

        if ($refundStatus === 'chargeback') {
            $payload['provider_dispute_id'] = 'dp_sandbox_lost';
        }

        if ($payment) {
            $payment->fill($payload)->save();

            return;
        }

        Payment::withoutGlobalScopes()->create($payload);
    }
}
