<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Support\TenantHostResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TenantSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $billingUrl;

    public function __construct(
        public Tenant $tenant,
        public string $reason,
    ) {
        $this->billingUrl = $this->resolveBillingUrl();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Ledrix workspace has been suspended',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-suspended',
        );
    }

    private function resolveBillingUrl(): string
    {
        try {
            return TenantHostResolver::workspacePanelUrlsForTenant($this->tenant)['admin'].'/organization/billing';
        } catch (Throwable) {
            return rtrim((string) config('app.url'), '/');
        }
    }
}
