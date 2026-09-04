<?php

namespace App\Services\Tenant;

use App\Mail\TenantDunningMail;
use App\Models\Central\TenantMembership;
use App\Support\SafeMail;
use Illuminate\Support\Facades\Schema;

class TenantDunningService
{
    /**
     * Mark membership past_due and send the day-0 dunning notice.
     */
    public function markPastDue(TenantMembership $membership): void
    {
        if ($membership->status !== 'past_due') {
            $membership->forceFill(['status' => 'past_due'])->save();
        }

        if ($this->columnsReady() && ! $membership->past_due_at) {
            $membership->forceFill(['past_due_at' => now()])->save();
        }

        $this->sendStep($membership->fresh(['tenant.plan']), 0);
    }

    /**
     * Send 3-day and 7-day (config) notices for memberships already past due.
     */
    public function sendScheduledNotices(): int
    {
        if (! $this->columnsReady()) {
            return 0;
        }

        $sent = 0;
        $steps = $this->ladderDays();

        $memberships = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('status', 'past_due')
            ->whereNotNull('past_due_at')
            ->get();

        foreach ($memberships as $membership) {
            $tenant = $membership->tenant;
            if (! $tenant || $tenant->isSuspended() || $tenant->isCancelled()) {
                continue;
            }

            $daysIn = max(0, (int) $membership->past_due_at->diffInDays(now()));

            foreach ($steps as $step) {
                if ($step === 0) {
                    continue;
                }
                if ($daysIn >= $step && $this->sendStep($membership, $step)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    public function sendStep(TenantMembership $membership, int $stepDays): bool
    {
        if (! $this->columnsReady()) {
            return false;
        }

        $column = $this->columnForStep($stepDays);
        if (! $column || $membership->{$column}) {
            return false;
        }

        $tenant = $membership->tenant;
        if (! $tenant?->email) {
            return false;
        }

        $sent = SafeMail::send(
            $tenant->email,
            new TenantDunningMail($tenant, $membership, $stepDays),
            'dunning email',
            [
                'tenant_id'     => $tenant->id,
                'membership_id' => $membership->id,
                'step_days'     => $stepDays,
            ],
        );

        if (! $sent) {
            return false;
        }

        $membership->forceFill([$column => now()])->save();

        return true;
    }

    /** @return list<int> */
    public function ladderDays(): array
    {
        $days = config('subscription.dunning_ladder_days', [0, 3, 7]);

        return array_values(array_unique(array_map('intval', $days)));
    }

    public function columnForStep(int $stepDays): ?string
    {
        return match ($stepDays) {
            0 => 'dunning_notice_0_sent_at',
            3 => 'dunning_notice_3_sent_at',
            7 => 'dunning_notice_7_sent_at',
            default => null,
        };
    }

    private function columnsReady(): bool
    {
        try {
            return Schema::connection('central')->hasColumn('tenant_memberships', 'dunning_notice_0_sent_at');
        } catch (Throwable) {
            return false;
        }
    }
}
