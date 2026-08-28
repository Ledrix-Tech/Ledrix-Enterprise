<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Support\TenantHostResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class TenantTeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $loginUrl;

    public string $roleLabel;

    public function __construct(
        public Tenant $tenant,
        public string $memberName,
        public string $role,
    ) {
        $this->loginUrl = $this->resolveLoginUrl();
        $this->roleLabel = Str::headline($role);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been added to '.$this->tenant->name.' on Ledrix',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-team-invite',
        );
    }

    private function resolveLoginUrl(): string
    {
        try {
            return TenantHostResolver::workspacePanelUrlsForTenant($this->tenant)['admin'].'/login';
        } catch (Throwable) {
            return rtrim((string) config('app.url'), '/').'/admin/login';
        }
    }
}
