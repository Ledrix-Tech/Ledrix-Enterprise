<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantDunningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantMembership $membership,
        public int $stepDays,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->stepDays) {
            0 => 'Action required — Ledrix payment failed',
            3 => 'Reminder — Ledrix CRM is in a billing grace period',
            default => 'Final notice — Ledrix access ends soon',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-dunning',
        );
    }
}
