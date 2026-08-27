<?php

namespace App\Services\Billing;

use App\Models\Central\TenantInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TenantInvoicePdfService
{
    public function absolutePath(TenantInvoice $invoice): string
    {
        return Storage::disk('local')->path($this->relativePath($invoice));
    }

    public function relativePath(TenantInvoice $invoice): string
    {
        return 'tenant-invoices/'.$invoice->tenant_id.'/'.$invoice->invoice_number.'.pdf';
    }

    /**
     * Ensure a PDF exists on disk and pdf_path is set; regenerate when missing.
     */
    public function ensure(TenantInvoice $invoice): TenantInvoice
    {
        $invoice->loadMissing(['tenant.plan', 'payment', 'membership']);

        $relative = $this->relativePath($invoice);
        $disk = Storage::disk('local');

        if ($invoice->pdf_path && $disk->exists($invoice->pdf_path)) {
            return $invoice;
        }

        $pdf = Pdf::loadView('pdf.tenant-invoice', [
            'invoice'  => $invoice,
            'tenant'   => $invoice->tenant,
            'payment'  => $invoice->payment,
            'taxLabel' => (string) config('services.invoice_tax.label', 'Tax'),
        ])->setPaper('a4', 'portrait');

        $disk->put($relative, $pdf->output());

        $invoice->forceFill(['pdf_path' => $relative])->save();

        return $invoice->fresh(['tenant.plan', 'payment', 'membership']);
    }

    public function download(TenantInvoice $invoice)
    {
        $invoice = $this->ensure($invoice);

        return response()->download(
            $this->absolutePath($invoice),
            $invoice->invoice_number.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
