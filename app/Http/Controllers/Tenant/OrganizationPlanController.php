<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;
use App\Services\Billing\ChangeTenantPlanService;
use App\Services\Billing\TenantStripeCheckoutService;
use App\Services\Tenant\TenantFeatureService;
use App\Services\Tenant\TenantUsageService;
use Illuminate\Http\Request;
use RuntimeException;

class OrganizationPlanController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(TenantFeatureService $features, TenantUsageService $usageService, ChangeTenantPlanService $planChanges)
    {
        $tenant = $this->organizationTenant();
        $tenant->load(['plan', 'featureOverride', 'limitOverride', 'activeMembership']);

        $featureMatrix = collect($features->matrixForTenant($tenant))
            ->groupBy('group');

        $usage = $usageService->syncSnapshot((int) $tenant->id);
        $plan = $tenant->plan;

        $limits = [
            ['label' => 'Brands', 'used' => $usage->total_brands, 'max' => $plan?->max_brands],
            ['label' => 'Sellers', 'used' => $usage->total_sellers, 'max' => $plan?->max_sellers],
            ['label' => 'Admins', 'used' => $usage->total_admins, 'max' => $plan?->max_admins],
            ['label' => 'Clients', 'used' => $usage->total_clients, 'max' => $plan?->max_clients],
            ['label' => 'Orders', 'used' => $usage->total_orders, 'max' => $plan?->max_orders],
            ['label' => 'Leads / month', 'used' => $usage->leads_this_month, 'max' => $plan?->max_leads_per_month],
        ];

        return $this->organizationView('plan', [
            'tenant'            => $tenant,
            'plan'              => $plan,
            'featureMatrix'     => $featureMatrix,
            'limits'            => $limits,
            'availablePlans'    => $planChanges->publicPlans(),
            'pendingPlanChange' => $tenant->meta['pending_plan_change'] ?? null,
        ]);
    }

    public function change(
        Request $request,
        ChangeTenantPlanService $planChanges,
        TenantStripeCheckoutService $stripeCheckout,
    ) {
        $tenant = $this->organizationTenant();

        $validated = $request->validate([
            'plan_id' => ['required', 'integer'],
            'timing'  => ['required', 'in:period_end,immediate'],
        ]);

        $target = PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->findOrFail($validated['plan_id']);

        try {
            $result = $planChanges->change($tenant, $target, $validated['timing']);
        } catch (RuntimeException $e) {
            return $this->organizationRedirect('plan', [], 'error', $e->getMessage());
        }

        if (($result['mode'] ?? '') === 'immediate_upgrade'
            && ($result['checkout_gateway'] ?? '') === 'stripe'
            && isset($result['payment'])) {
            try {
                $url = $stripeCheckout->createCheckoutUrl(
                    $tenant,
                    $result['payment'],
                    successUrl: route('tenant.billing.stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
                    cancelUrl: route($this->organizationRouteName('billing')).'?cancelled=1',
                );

                return redirect()->away($url);
            } catch (RuntimeException $e) {
                return $this->organizationRedirect('billing', [], 'error', $e->getMessage());
            }
        }

        if (($result['mode'] ?? '') === 'immediate_upgrade') {
            return $this->organizationRedirect('billing', [], 'success', $result['message']);
        }

        return $this->organizationRedirect('plan', [], 'success', $result['message']);
    }
}
