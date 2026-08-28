<?php

namespace App\Mail;

use App\Models\Central\TenantInvoice;
use App\Support\TenantHostResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TenantInvoiceVoidedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $billingUrl;

    public function __construct(
        public TenantInvoice $invoice,
        public ?string $reason = null,
    ) {
        $this->invoice->loadMissing('tenant');
        $this->billingUrl = $this->resolveBillingUrl();
    }

    public function envelope(): Envelope
    {
        $number = $this->invoice->invoice_number ?: '#'.$this->invoice->id;

        return new Envelope(
            subject: 'Invoice '.$number.' was voided',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-invoice-voided',
        );
    }

    private function resolveBillingUrl(): string
    {
        $tenant = $this->invoice->tenant;
        if (! $tenant) {
            return rtrim((string) config('app.url'), '/');
        }

        try {
            return TenantHostResolver::workspacePanelUrlsForTenant($tenant)['admin'].'/organization/billing';
        } catch (Throwable) {
            return rtrim((string) config('app.url'), '/');
        }
    }
}
