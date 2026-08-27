<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantStorageAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public int $usedMb,
        public int $limitMb,
        public int $thresholdPercent,
    ) {}

    public function envelope(): Envelope
    {
        $level = $this->thresholdPercent >= 100 ? 'full' : 'nearly full';

        return new Envelope(
            subject: "Ledrix storage {$level} ({$this->thresholdPercent}% of plan limit)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-storage-alert',
        );
    }
}
