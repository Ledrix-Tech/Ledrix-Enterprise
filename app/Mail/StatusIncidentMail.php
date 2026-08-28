<?php

namespace App\Mail;

use App\Models\Central\PlatformStatusIncident;
use App\Models\Central\PlatformStatusSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusIncidentMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $statusUrl;

    public string $unsubscribeUrl;

    public function __construct(
        public PlatformStatusIncident $incident,
        public PlatformStatusSubscriber $subscriber,
        public string $event = 'published',
    ) {
        $this->statusUrl = route('status.get');
        $this->unsubscribeUrl = route('status.unsubscribe', ['token' => $subscriber->token]);
    }

    public function envelope(): Envelope
    {
        $verb = $this->event === 'resolved' ? 'resolved' : ($this->event === 'updated' ? 'updated' : 'published');

        return new Envelope(
            subject: '[Ledrix status] Incident '.$verb.': '.$this->incident->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.status-incident',
        );
    }
}
