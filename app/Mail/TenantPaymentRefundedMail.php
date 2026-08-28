<?php

namespace App\Mail;

use App\Models\Central\TenantPayment;
use App\Support\TenantHostResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TenantPaymentRefundedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $billingUrl;

    public function __construct(
        public TenantPayment $payment,
        public float $refundAmount,
        public float $creditApplied,
        public bool $stripeRefunded,
        public ?string $reason = null,
    ) {
        $this->payment->loadMissing(['tenant', 'invoice']);
        $this->billingUrl = $this->resolveBillingUrl();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A refund was recorded on your Ledrix account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-payment-refunded',
        );
    }

    private function resolveBillingUrl(): string
    {
        $tenant = $this->payment->tenant;
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
