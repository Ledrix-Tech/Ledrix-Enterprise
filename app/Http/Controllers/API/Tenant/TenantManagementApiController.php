<?php

namespace App\Http\Controllers\API\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantUsageSnapshot;
use App\Services\Tenant\TenantUsageService;
use Illuminate\Http\Request;

class TenantManagementApiController extends Controller
{
    public function company(Request $request)
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('company');

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $tenant->id,
                'name'       => $tenant->name,
                'slug'       => $tenant->slug,
                'email'      => $tenant->email,
                'status'     => $tenant->status,
                'country'    => $tenant->country,
                'website'    => $tenant->website,
                'plan'       => $tenant->plan ? [
                    'id'   => $tenant->plan->id,
                    'name' => $tenant->plan->name,
                    'slug' => $tenant->plan->slug,
                ] : null,
                'created_at' => $tenant->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function membership(Request $request)
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('company');

        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->latest('start_date')
            ->first();

        if (! $membership) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'No current membership.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $membership->id,
                'status'        => $membership->status,
                'billing_cycle' => $membership->billing_cycle,
                'amount'        => (string) $membership->amount,
                'currency'      => $membership->currency,
                'start_date'    => $membership->start_date?->toDateString(),
                'end_date'      => $membership->end_date?->toDateString(),
                'plan_id'       => $membership->plan_id,
            ],
        ]);
    }

    public function invoices(Request $request)
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('company');

        $invoices = TenantInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->limit(50)
            ->get()
            ->map(fn (TenantInvoice $invoice) => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status'         => $invoice->status,
                'amount'         => (string) $invoice->amount,
                'tax_amount'     => (string) $invoice->tax_amount,
                'total_amount'   => (string) $invoice->total_amount,
                'currency'       => $invoice->currency,
                'billing_cycle'  => $invoice->billing_cycle,
                'issued_at'      => $invoice->issued_at?->toIso8601String(),
                'due_at'         => $invoice->due_at?->toIso8601String(),
                'paid_at'        => $invoice->paid_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => $invoices,
        ]);
    }

    public function usage(Request $request, TenantUsageService $usageService)
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('company');

        try {
            $usageService->syncSnapshot((int) $tenant->id);
        } catch (\Throwable) {
            // Primary may be unavailable; return last snapshot if any.
        }

        $snapshot = TenantUsageSnapshot::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_brands'      => (int) ($snapshot->total_brands ?? 0),
                'total_sellers'     => (int) ($snapshot->total_sellers ?? 0),
                'total_admins'      => (int) ($snapshot->total_admins ?? 0),
                'total_clients'     => (int) ($snapshot->total_clients ?? 0),
                'total_orders'      => (int) ($snapshot->total_orders ?? 0),
                'leads_this_month'  => (int) ($snapshot->leads_this_month ?? 0),
                'storage_mb'        => (int) ($snapshot->storage_used_mb ?? 0),
                'synced_at'         => $snapshot?->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
