<?php

namespace App\Services\Tenant;

use App\Mail\TenantSubscriptionExpiredMail;
use App\Mail\TenantSubscriptionRenewalReminderMail;
use App\Models\Central\TenantMembership;
use App\Support\SafeMail;

class ProcessTenantSubscriptionsService
{
    public function __construct(
        private readonly TenantDunningService $dunning,
    ) {}

    public function run(): array
    {
        return [
            'reminders_7d'    => $this->sendRenewalReminders(7, 'renewal_reminder_7d_sent_at'),
            'reminders_3d'    => $this->sendRenewalReminders(3, 'renewal_reminder_3d_sent_at'),
            'reminders_1d'    => $this->sendRenewalReminders(1, 'renewal_reminder_1d_sent_at'),
            'marked_past_due' => $this->markExpiredActiveAsPastDue(),
            'dunning_sent'    => $this->dunning->sendScheduledNotices(),
            'expired'         => $this->expireOverdueMemberships(),
        ];
    }

    private function sendRenewalReminders(int $daysBefore, string $sentAtColumn): int
    {
        if (! in_array($daysBefore, config('subscription.renewal_reminder_days', [7, 3, 1]), true)) {
            return 0;
        }

        $count = 0;
        $targetDate = now()->addDays($daysBefore)->toDateString();

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'active')
            ->whereNull($sentAtColumn)
            ->whereNotNull('end_date')
            ->whereDate('end_date', $targetDate)
            ->get();

        foreach ($memberships as $membership) {
            $tenant = $membership->tenant;

            if (! $tenant || $tenant->isSuspended() || $tenant->isCancelled()) {
                continue;
            }

            $daysLeft = $membership->daysUntilExpiry();

            $sent = SafeMail::send(
                $tenant->email,
                new TenantSubscriptionRenewalReminderMail($tenant, $membership, $daysLeft),
                'subscription renewal reminder',
                [
                    'tenant_id'     => $tenant->id,
                    'membership_id' => $membership->id,
                    'days_before'   => $daysBefore,
                ],
            );

            if ($sent) {
                $membership->update([$sentAtColumn => now()]);
                $count++;
            }
        }

        return $count;
    }

    private function markExpiredActiveAsPastDue(): int
    {
        $count = 0;

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->get();

        foreach ($memberships as $membership) {
            $this->dunning->markPastDue($membership);
            $count++;
        }

        return $count;
    }

    private function expireOverdueMemberships(): int
    {
        $graceDays = (int) config('subscription.past_due_grace_days', 7);
        $count = 0;

        $memberships = TenantMembership::query()
            ->where('status', 'past_due')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->subDays($graceDays)->toDateString())
            ->get();

        foreach ($memberships as $membership) {
            $membership->update(['status' => 'expired']);
            $membership->loadMissing('tenant.plan');
            $tenant = $membership->tenant;
            if ($tenant) {
                SafeMail::send(
                    $tenant->email,
                    new TenantSubscriptionExpiredMail($tenant, $membership),
                    'subscription expired',
                    ['tenant_id' => $tenant->id, 'membership_id' => $membership->id],
                );
            }
            $count++;
        }

        return $count;
    }
}
