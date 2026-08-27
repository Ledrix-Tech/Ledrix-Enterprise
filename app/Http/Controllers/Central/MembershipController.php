<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use App\Services\Billing\TenantBillingRegion;
use App\Services\Billing\TenantInvoicePdfService;
use App\Services\Central\SuperAdminTenantFeatureService;
use App\Services\Central\SuperAdminTenantLimitService;
use App\Support\TenantHostResolver;
use App\Services\Tenant\CustomDomainVerificationService;
use App\Services\Tenant\TenantFeatureService;
use App\Services\Tenant\TenantLifecycleService;
use App\Services\Tenant\TenantUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class MembershipController extends Controller
{
    public function superCompanyProfiles()
    {
        $companies = Tenant::with(['plan:id,name,slug', 'activeMembership'])
            ->latest()
            ->paginate(20);

        return view('central.pages.company-profiles', compact('companies'));
    }

    public function superTenantShow(
        $id,
        TenantFeatureService $features,
        SuperAdminTenantFeatureService $featureOverrides,
        SuperAdminTenantLimitService $limitOverrides,
        TenantUsageService $usageService,
        CustomDomainVerificationService $domains,
    ) {
        $query = Tenant::with([
            'plan',
            'featureOverride',
            'limitOverride',
            'activeMembership.plan',
            'memberships' => fn ($q) => $q->latest()->take(5),
            'payments'    => fn ($q) => $q->latest()->take(8),
        ]);

        if (request()->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $tenant = $query->findOrFail($id);

        $pendingPayments = TenantPayment::query()
            ->with('invoice')
            ->where('tenant_id', $tenant->id)
            ->whereIn('gateway', ['payoneer', 'bank_transfer'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $invoices = TenantInvoice::query()
            ->with('payment')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->get();

        $limitSummary = collect($limitOverrides->matrixForTenant($tenant))->map(function (array $limit) {
            $effective = (int) ($limit['effective'] ?? 0);
            $used = (int) ($limit['used'] ?? 0);
            $unlimited = $effective === -1;

            $limit['unlimited'] = $unlimited;
            $limit['percent'] = (! $unlimited && $effective > 0)
                ? min(100, round(($used / $effective) * 100, 1))
                : 0;

            return $limit;
        });

        try {
            $usageService->syncSnapshot((int) $tenant->id);
        } catch (\Throwable) {
            // Primary DB may be unavailable in some environments; live matrix still works.
        }

        $recentAuditLogs = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $apiTokens = TenantApiToken::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $verifyToken = $tenant->custom_domain
            ? $domains->ensureVerificationToken($tenant->fresh())
            : null;

        return view('central.pages.tenant-detail', [
            'tenant'             => $tenant->fresh(),
            'pendingPayments'    => $pendingPayments,
            'invoices'           => $invoices,
            'featureSummary'     => collect($features->matrixForTenant($tenant))->filter(fn ($f) => $f['effective']),
            'limitSummary'       => $limitSummary,
            'hasOverrides'       => $featureOverrides->hasAnyOverride($tenant)
                || $limitOverrides->hasAnyOverride($tenant),
            'billingCurrency'    => TenantBillingRegion::currencyForTenant($tenant),
            'billingRegionLabel' => TenantBillingRegion::regionLabel($tenant),
            'recentAuditLogs'    => $recentAuditLogs,
            'apiTokens'          => $apiTokens,
            'hasCustomDomainFeature' => $features->enabled('custom_domain', (int) $tenant->id),
            'domainVerifyToken'  => $verifyToken,
            'domainPlatformHost' => $domains->platformHost(),
            'domainTxtHost'      => $tenant->custom_domain
                ? '_ledrix-verify.'.$domains->normalize((string) $tenant->custom_domain)
                : null,
            'workspaceHost'        => TenantHostResolver::workspaceHostForSlug($tenant->slug),
            'workspaceBaseUrl'     => TenantHostResolver::workspaceBaseUrlForTenant($tenant),
            'workspacePanelUrls'   => TenantHostResolver::workspacePanelUrlsForTenant($tenant),
            'tenantCrmDatabase'    => $tenant->crm_database,
        ]);
    }

    public function superTenantInvoiceShow(int $tenantId, int $invoiceId)
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $invoice = TenantInvoice::query()
            ->with(['payment', 'membership'])
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoiceId);

        return view('central.pages.tenant-invoice-show', [
            'tenant'  => $tenant,
            'invoice' => $invoice,
            'payment' => $invoice->payment,
        ]);
    }

    public function superTenantInvoicePdf(int $tenantId, int $invoiceId, TenantInvoicePdfService $pdfs)
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $invoice = TenantInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoiceId);

        return $pdfs->download($invoice);
    }

    public function superTenantInvoiceVoid(int $tenantId, int $invoiceId, Request $request, \App\Services\Billing\VoidTenantInvoiceService $voids)
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $invoice = TenantInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoiceId);

        try {
            $voids->void($invoice, $request->input('reason'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice voided.');
    }

    public function superTenantInvoiceRefund(int $tenantId, int $invoiceId, Request $request, \App\Services\Billing\RefundTenantPaymentService $refunds)
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $invoice = TenantInvoice::query()
            ->with('payment')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoiceId);

        if (! $invoice->payment) {
            return back()->with('error', 'Invoice has no payment to refund.');
        }

        try {
            $result = $refunds->refund(
                $invoice->payment,
                reason: $request->input('reason'),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = $result['stripe_refunded']
            ? 'Stripe refund submitted.'
            : ($result['credit_applied'] > 0
                ? 'Refund recorded and '.$result['credit_applied'].' credited to tenant balance.'
                : 'Refund recorded.');

        return back()->with('success', $msg);
    }

    /**
     * Legacy JSON endpoint used by tenants.js — routes through lifecycle service.
     */
    public function superTenantStatus(Request $request, TenantLifecycleService $lifecycle)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'status'  => ['required', 'string', Rule::in(['active', 'suspended'])],
            'reason'  => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = Tenant::query()->find($validated['user_id']);
        if (! $tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found.'], 404);
        }

        $actor = Auth::guard('super_admin')->user();

        try {
            if ($validated['status'] === 'suspended') {
                $reason = trim((string) ($validated['reason'] ?? ''));
                if ($reason === '') {
                    return response()->json([
                        'success' => false,
                        'message' => 'A suspend reason is required.',
                    ], 422);
                }
                $lifecycle->suspend($tenant, $reason, 'super_admin', $actor?->id, $actor?->name);
            } else {
                $lifecycle->activate($tenant, 'super_admin', $actor?->id, $actor?->name);
            }
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function suspend(Request $request, int $id, TenantLifecycleService $lifecycle)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:500'],
        ]);

        $tenant = Tenant::query()->findOrFail($id);
        $actor = Auth::guard('super_admin')->user();

        try {
            $lifecycle->suspend($tenant, $validated['reason'], 'super_admin', $actor?->id, $actor?->name);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Tenant suspended. CRM and portal access are blocked.');
    }

    public function activate(int $id, TenantLifecycleService $lifecycle)
    {
        $tenant = Tenant::query()->findOrFail($id);
        $actor = Auth::guard('super_admin')->user();

        try {
            $lifecycle->activate($tenant, 'super_admin', $actor?->id, $actor?->name);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Tenant activated.');
    }

    public function offboard(Request $request, int $id, TenantLifecycleService $lifecycle)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:500'],
        ]);

        $tenant = Tenant::query()->findOrFail($id);
        $actor = Auth::guard('super_admin')->user();

        try {
            $lifecycle->offboard($tenant, $validated['reason'], 'super_admin', $actor?->id, $actor?->name);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('super-admin.company-profile.get')
            ->with('success', 'Tenant offboarded (soft-deleted). Billing history retained.');
    }

    public function restoreOffboarded(int $id, TenantLifecycleService $lifecycle)
    {
        $tenant = Tenant::withTrashed()->findOrFail($id);
        $actor = Auth::guard('super_admin')->user();

        try {
            $lifecycle->restore($tenant, 'super_admin', $actor?->id, $actor?->name);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('super-admin.tenant.show', $tenant->id)
            ->with('success', 'Tenant restored as inactive. Activate when ready.');
    }
}
