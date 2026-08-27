<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;

class SubscriptionAccessService
{
    public function canUseCrm(Tenant $tenant): bool
    {
        if ($tenant->isSuspended() || $tenant->isCancelled()) {
            return false;
        }

        if (! $tenant->isEmailVerified()) {
            return false;
        }

        $membership = $this->currentMembership($tenant);

        if (! $membership) {
            return false;
        }

        if ($membership->status === 'active' && ! $membership->isExpired()) {
            return true;
        }

        // F-02: CRM grace while past_due within configured window after end_date.
        if ($membership->status === 'past_due' && $membership->end_date) {
            $graceDays = (int) config('subscription.past_due_grace_days', 7);
            if ($membership->end_date->copy()->addDays($graceDays)->isFuture()) {
                return true;
            }
        }

        if ($membership->status === 'trialing' && $tenant->isOnTrial()) {
            return true;
        }

        return false;
    }

    /**
     * Expired / past-due tenants may still open Admin Organization Billing (and related org portal)
     * to renew — but not the rest of the CRM.
     */
    public function canAccessOrgBilling(Tenant $tenant): bool
    {
        if ($tenant->isSuspended() || $tenant->isCancelled()) {
            return false;
        }

        if (! $tenant->isEmailVerified()) {
            return false;
        }

        // Any tenant with a membership (or who can pay) may open billing to restore access.
        return $this->currentMembership($tenant) !== null
            || $this->canPayOnBilling($tenant)
            || $this->needsPayment($tenant);
    }

    public function needsPayment(Tenant $tenant): bool
    {
        $membership = $this->currentMembership($tenant);

        if (! $membership) {
            return false;
        }

        return in_array($membership->status, ['past_due', 'trialing_restricted', 'expired'], true)
            || ($membership->status === 'active' && $membership->isExpired())
            || ($membership->status === 'trialing' && ! $tenant->isOnTrial());
    }

    public function canPayOnBilling(Tenant $tenant): bool
    {
        if ($tenant->isSuspended() || $tenant->isCancelled()) {
            return false;
        }

        $membership = $this->currentMembership($tenant);

        if (! $membership) {
            return false;
        }

        if ($this->needsPayment($tenant)) {
            return true;
        }

        $hasPendingUpgrade = $this->hasPendingUpgradePayment((int) $tenant->id);

        if ($hasPendingUpgrade) {
            return true;
        }

        if ($tenant->isOnTrial()) {
            return true;
        }

        $earlyRenewDays = (int) config('subscription.early_renew_days', 7);

        return $membership->status === 'active'
            && ! $membership->isExpired()
            && $membership->expiresSoon($earlyRenewDays);
    }

    public function paymentOrderType(Tenant $tenant): string
    {
        $membership = $this->currentMembership($tenant);

        if ($this->hasPendingUpgradePayment((int) ($tenant->id ?? 0))) {
            return 'upgrade';
        }

        if ($membership && in_array($membership->status, ['active', 'past_due', 'expired'], true)) {
            return 'renewal';
        }

        return 'new';
    }

    public function expiresSoon(Tenant $tenant): bool
    {
        $membership = $this->currentMembership($tenant);

        if (! $membership || $membership->status !== 'active' || $membership->isExpired()) {
            return false;
        }

        return $membership->expiresSoon((int) config('subscription.early_renew_days', 7));
    }

    public function isOnTrial(Tenant $tenant): bool
    {
        $membership = $this->currentMembership($tenant);

        return $membership
            && $membership->status === 'trialing'
            && $tenant->isOnTrial();
    }

    public function currentMembership(Tenant $tenant): ?TenantMembership
    {
        return $tenant->memberships()
            ->latest('start_date')
            ->first();
    }

    private function hasPendingUpgradePayment(int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        try {
            return TenantPayment::query()
                ->where('tenant_id', $tenantId)
                ->where('order_type', 'upgrade')
                ->where('status', 'pending')
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}

