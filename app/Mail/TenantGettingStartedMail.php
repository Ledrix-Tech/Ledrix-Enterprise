<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Support\GettingStartedGuide;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantGettingStartedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Getting started with your Ledrix workspace',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-getting-started',
            with: [
                'tenant'  => $this->tenant,
                'steps'   => GettingStartedGuide::steps(),
                'intro'   => GettingStartedGuide::intro(),
                'helpUrl' => route('tenant.getting-started'),
                'crmUrl'  => route('tenant.dashboard'),
            ],
        );
    }
}
