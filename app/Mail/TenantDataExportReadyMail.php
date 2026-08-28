<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Models\Central\TenantDataExportRequest;
use App\Support\TenantHostResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantDataExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $exportUrl;

    public function __construct(
        public Tenant $tenant,
        public TenantDataExportRequest $export,
    ) {
        $this->exportUrl = TenantHostResolver::workspacePanelUrlsForTenant($tenant)['admin']
            .'/organization/data-export';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Ledrix workspace export is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-data-export-ready',
        );
    }
}
