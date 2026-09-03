<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyImportedContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $leadId)
    {
    }

    public function handle(): void
    {
        $lead = Lead::query()->find($this->leadId);
        if (! $lead) {
            return;
        }

        $email = strtolower(trim((string) $lead->email));
        $phone = preg_replace('/\D+/', '', (string) $lead->phone) ?: '';

        $meta = is_array($lead->meta) ? $lead->meta : [];
        $meta['import_contact_check'] = [
            'email_ok' => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
            'phone_ok' => $phone === '' || strlen($phone) >= 7,
            'checked_at' => now()->toIso8601String(),
        ];

        $lead->forceFill(['meta' => $meta])->save();
    }
}
