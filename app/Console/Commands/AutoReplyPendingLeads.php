<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;

class AutoReplyPendingLeads extends Command
{
    protected $signature = 'leads:auto-reply';
    protected $description = 'Auto reply to leads not updated within 24 hours after reaching seller';

    public function handle()
    {
        $this->info('Checking for pending leads...');

        $leads = Lead::where('status', 'new')
            ->where('created_at', '<=', now()->subHours(24))
            ->whereNull('auto_replied')
            ->get();

        foreach ($leads as $lead) {
            $sent = \App\Support\SafeMail::sendView(
                $lead->email,
                'emails.lead-follow-up',
                ['lead' => $lead],
                'We’re still here to help you!',
                'lead follow-up',
                ['lead_id' => $lead->id],
            );

            if (! $sent) {
                continue;
            }

            $lead->update(['auto_replied' => true]);
            $this->info("Auto replied to lead ID: {$lead->id}");
        }

        $this->info('Auto-reply process complete.');
    }
}
