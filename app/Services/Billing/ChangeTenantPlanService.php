<?php

namespace App\Services\Billing;

use App\Models\Central\AuditLog;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * F-10: Self-serve plan change (period-end schedule or immediate upgrade with proration invoice).
 */
class ChangeTenantPlanService
{
    public function __construct(
        private readonly SubscriptionAccessService $access,
        private readonly SubscriptionPricingService $pricing,
        private readonly CreateSubscriptionInvoiceService $invoices,
        private readonly PlatformBillingSettingsService $platformBilling,
    ) {}

    /**
     * @return list<PackagePricing>
     */
    public function publicPlans(): array
    {
        return PackagePricing::query()
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get()
            ->all();
    }

    /**
     * @return array{mode: string, message: string, payment?: \App\Models\Central\TenantPayment, invoice?: \App\Models\Central\TenantInvoice, checkout_gateway?: string}
     */
    public function change(Tenant $tenant, PackagePricing $target, string $timing = 'period_end'): array
    {
        $tenant->load(['plan', 'activeMembership']);
        $membership = $this->access->currentMembership($tenant);

        if (! $membership || $membership->status !== 'active' || $membership->isExpired()) {
            throw new RuntimeException('Plan changes require an active subscription. Renew billing first.');
        }

        if ((int) $tenant->plan_id === (int) $target->id) {
            throw new RuntimeException('You are already on this plan.');
        }

        $current = $tenant->plan;
        if (! $current) {
            throw new RuntimeException('Current plan not found.');
        }

        $currency = TenantBillingRegion::syncPreferredCurrency($tenant);
        $currentPrice = $this->pricing->resolveAmount($current, $membership->billing_cycle ?? 'monthly', $currency);
        $targetPrice = $this->pricing->resolveAmount($target, $membership->billing_cycle ?? 'monthly', $currency);
        $isUpgrade = $targetPrice > $currentPrice;

        if ($timing === 'period_end' || ! $isUpgrade) {
            $meta = is_array($tenant->meta) ? $tenant->meta : [];
            $meta['pending_plan_change'] = [
                'plan_id'      => $target->id,
                'plan_name'    => $target->name,
                'effective'    => 'period_end',
                'requested_at' => now()->toIso8601String(),
            ];
            $tenant->forceFill(['meta' => $meta])->save();

            $this->audit($tenant, 'tenant.plan_change_scheduled', [
                'description' => 'Plan change scheduled for period end: '.$target->name,
                'after'       => $meta['pending_plan_change'],
            ]);

            return [
                'mode'    => 'period_end',
                'message' => 'Plan will switch to '.$target->name.' when your current period renews ('.$membership->end_date?->format('M d, Y').').',
            ];
        }

        // Immediate upgrade: prorate remaining fraction of the period.
        $daysTotal = max(1, (int) ($membership->start_date?->diffInDays($membership->end_date) ?: 30));
        $daysLeft = max(1, (int) now()->diffInDays($membership->end_date, false));
        $fraction = min(1, max(0, $daysLeft / $daysTotal));
        $proration = BillingMoney::round(max(0, ($targetPrice - $currentPrice) * $fraction), $currency);

        if ($proration < 1) {
            $meta = is_array($tenant->meta) ? $tenant->meta : [];
            $meta['pending_plan_change'] = [
                'plan_id'      => $target->id,
                'plan_name'    => $target->name,
                'effective'    => 'period_end',
                'requested_at' => now()->toIso8601String(),
            ];
            $tenant->forceFill(['meta' => $meta])->save();

            return [
                'mode'    => 'period_end',
                'message' => 'Proration is too small to charge now. Plan change scheduled for period end.',
            ];
        }

        $gateway = $this->resolveGateway($tenant, $currency);
        $result = $this->invoices->createForTenant(
            $tenant,
            $gateway,
            $currency,
            'upgrade',
            $target,
            $proration,
        );

        $this->audit($tenant, 'tenant.plan_upgrade_invoiced', [
            'description' => 'Immediate upgrade invoice for '.$target->name,
            'after'       => [
                'plan_id'    => $target->id,
                'proration'  => $proration,
                'currency'   => $currency,
                'payment_id' => $result['payment']->id,
            ],
        ]);

        return [
            'mode'             => 'immediate_upgrade',
            'message'          => 'Upgrade invoice issued. Pay to activate '.$target->name.' now.',
            'payment'          => $result['payment'],
            'invoice'          => $result['invoice'],
            'checkout_gateway' => $gateway,
        ];
    }

    public function applyPendingOnRenewal(Tenant $tenant, TenantMembership $membership): void
    {
        $pending = $tenant->meta['pending_plan_change'] ?? null;
        if (! is_array($pending) || empty($pending['plan_id'])) {
            return;
        }

        $plan = PackagePricing::query()->find((int) $pending['plan_id']);
        if (! $plan) {
            return;
        }

        $membership->forceFill(['plan_id' => $plan->id])->save();
        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        unset($meta['pending_plan_change']);
        $tenant->forceFill([
            'plan_id' => $plan->id,
            'meta'    => $meta,
        ])->save();
    }

    private function resolveGateway(Tenant $tenant, string $currency): string
    {
        if ($currency === TenantBillingRegion::CURRENCY_PKR) {
            if ($this->platformBilling->isReady('meezan')) {
                return 'bank_transfer';
            }
            throw new RuntimeException('No PKR gateway is enabled for upgrades.');
        }

        if (! $this->platformBilling->isReady('stripe')) {
            throw new RuntimeException('Stripe is not enabled for international upgrades.');
        }

        return 'stripe';
    }

    /** @param array<string, mixed> $context */
    private function audit(Tenant $tenant, string $action, array $context): void
    {
        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user() ?? Auth::guard('super_admin')->user();

        AuditLog::record(
            $action,
            (int) $tenant->id,
            Auth::guard('super_admin')->check() ? 'super_admin' : (Auth::guard('admin')->check() ? 'admin' : 'tenant'),
            $actor?->id,
            $actor?->name ?? $tenant->name,
            array_merge([
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
            ], $context)
        );
    }
}
