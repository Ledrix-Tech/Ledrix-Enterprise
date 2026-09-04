<?php

namespace App\Mail;

use App\Support\SafeMail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentLinkCreated extends Mailable
{
    public function __construct(
        public string $url,
        public string $recipient,
        public string $brandName,
        public string $service,
        public string $amount,
        public mixed $expiresAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Secure Payment Link',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_link_created',
            with: [
                'url'        => $this->url,
                'recipient'  => $this->recipient,
                'brandName'  => $this->brandName,
                'service'    => $this->service,
                'amount'     => $this->amount,
                'expiresAt'  => $this->expiresAt,
            ],
        );
    }

    /**
     * Send immediately. Do not queue — production payment-link mail was stuck in jobs.
     *
     * @param  array<string, mixed>  $logExtra
     */
    public static function sendTo(
        ?string $to,
        string $url,
        string $recipient,
        string $brandName,
        string $service,
        string $amount,
        mixed $expiresAt = null,
        array $logExtra = [],
    ): bool {
        return SafeMail::send(
            $to,
            new self($url, $recipient, $brandName, $service, $amount, $expiresAt),
            'payment_link_email',
            $logExtra,
        );
    }
}
