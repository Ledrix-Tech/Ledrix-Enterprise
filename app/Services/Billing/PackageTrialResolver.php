<?php

namespace App\Services\Billing;

use App\Models\Central\PackagePricing;
use Carbon\Carbon;

/**
 * Resolves tenant trial dates and membership state from a package's trial_days.
 */
class PackageTrialResolver
{
    /**
     * @return array{
     *     trial_days: int,
     *     trial_ends_at: ?Carbon,
     *     membership_status: string,
     *     trial_start: ?string,
     *     trial_end: ?string,
     *     end_date: string,
     *     trial_used: bool
     * }
     */
    public function resolve(PackagePricing $package, ?Carbon $startsAt = null): array
    {
        $startsAt ??= now();
        $trialDays = max(0, (int) $package->trial_days);

        if ($trialDays > 0) {
            $trialEndsAt = $startsAt->copy()->addDays($trialDays);

            return [
                'trial_days'          => $trialDays,
                'trial_ends_at'       => $trialEndsAt,
                'membership_status'   => 'trialing',
                'trial_start'         => $startsAt->toDateString(),
                'trial_end'           => $trialEndsAt->toDateString(),
                'end_date'            => $trialEndsAt->toDateString(),
                'trial_used'          => true,
            ];
        }

        return [
            'trial_days'          => 0,
            'trial_ends_at'       => null,
            'membership_status'   => 'past_due',
            'trial_start'         => null,
            'trial_end'           => null,
            'end_date'            => $startsAt->toDateString(),
            'trial_used'          => false,
        ];
    }
}
