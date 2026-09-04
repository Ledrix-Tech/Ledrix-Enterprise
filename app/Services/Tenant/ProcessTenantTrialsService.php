<?php

namespace App\Services\Tenant;

use App\Mail\TenantSubscriptionExpiredMail;
use App\Mail\TenantTrialEndingMail;
use App\Models\Central\TenantMembership;
use App\Support\SafeMail;

class ProcessTenantTrialsService
{
    public function __construct(
        private readonly TenantDunningService $dunning,
    ) {}

    public function run(): array
    {
        return [
            'reminders_sent' => $this->sendTrialReminders(),
            'trials_ended'   => $this->markTrialsEnded(),
            'expired'        => $this->expireOverdueMemberships(),
        ];
    }

    private function sendTrialReminders(): int
    {
        $reminderDays = (int) config('subscription.trial_reminder_days', config('services.jazzcash.trial_reminder_days', 3));
        $count = 0;

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'trialing')
            ->whereNull('trial_reminder_sent_at')
            ->whereDate('trial_end', '<=', now()->addDays($reminderDays)->toDateString())
            ->whereDate('trial_end', '>=', now()->toDateString())
            ->get();

        foreach ($memberships as $membership) {
            $tenant = $membership->tenant;

            if (! $tenant || ! $tenant->isOnTrial()) {
                continue;
            }

            $sent = SafeMail::send(
                $tenant->email,
                new TenantTrialEndingMail($tenant, $membership),
                'trial reminder',
                ['tenant_id' => $tenant->id, 'membership_id' => $membership->id],
            );

            if ($sent) {
                $membership->update(['trial_reminder_sent_at' => now()]);
                $count++;
            }
        }

        return $count;
    }

    private function markTrialsEnded(): int
    {
        $count = 0;

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'trialing')
            ->whereDate('trial_end', '<', now()->toDateString())
            ->get();

        foreach ($memberships as $membership) {
            $this->dunning->markPastDue($membership);
            $count++;
        }

        return $count;
    }

    private function expireOverdueMemberships(): int
    {
        $graceDays = (int) config('subscription.past_due_grace_days', config('services.jazzcash.grace_days', 7));
        $count = 0;

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'past_due')
            ->whereDate('end_date', '<', now()->subDays($graceDays)->toDateString())
            ->get();

        foreach ($memberships as $membership) {
            $membership->update(['status' => 'expired']);
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
