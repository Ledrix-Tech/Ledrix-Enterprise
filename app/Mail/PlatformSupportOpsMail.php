<?php

namespace App\Mail;

use App\Models\Central\PlatformSupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PlatformSupportOpsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $ticketUrl;

    /**
     * @param  'created'|'tenant_replied'  $event
     */
    public function __construct(
        public PlatformSupportTicket $ticket,
        public string $event,
        public ?string $replyMessage = null,
    ) {
        $this->ticket->loadMissing('tenant:id,name,email,slug');
        $this->ticketUrl = $this->resolveTicketUrl();
    }

    public function envelope(): Envelope
    {
        $id = $this->ticket->id;
        $subject = $this->event === 'tenant_replied'
            ? "[Ledrix] Tenant replied on ticket #{$id}"
            : "[Ledrix] New support ticket #{$id}: {$this->ticket->subject}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.platform-support-ops',
            with: [
                'ticket'       => $this->ticket,
                'event'        => $this->event,
                'replyMessage' => $this->replyMessage,
                'url'          => $this->ticketUrl,
            ],
        );
    }

    private function resolveTicketUrl(): string
    {
        try {
            return route('super-admin.support-tickets.show', $this->ticket->id);
        } catch (Throwable) {
            return rtrim((string) config('app.url'), '/').'/super-admin/support-tickets/'.$this->ticket->id;
        }
    }
}
