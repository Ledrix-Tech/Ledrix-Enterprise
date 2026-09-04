<?php

namespace App\Mail;

use App\Models\Client;
use App\Support\SafeMail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ClientPortalAccessMail extends Mailable
{
    public function __construct(
        public string $clientName,
        public string $clientEmail,
        public string $password,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your CRM Portal Access',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-account-access',
            with: [
                'clientName'  => $this->clientName,
                'clientEmail' => $this->clientEmail,
                'password'    => $this->password,
                'loginUrl'    => $this->loginUrl,
            ],
        );
    }

    /**
     * Send immediately. Do not queue — shared hosting often leaves queued mail in jobs.
     *
     * @param  array<string, mixed>  $logExtra
     */
    public static function sendTo(Client $client, string $plainPassword, string $loginUrl, array $logExtra = []): bool
    {
        return SafeMail::send(
            $client->email,
            new self(
                (string) $client->name,
                (string) $client->email,
                $plainPassword,
                $loginUrl,
            ),
            'client_portal_access_email',
            $logExtra,
        );
    }
}
