<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\TenantInvoice;
use App\Services\Billing\TenantInvoicePdfService;

class TenantInvoiceController extends Controller
{
    use ResolvesOrganizationTenant;

    public function show(int $invoice)
    {
        $tenant = $this->organizationTenant();

        $invoiceModel = TenantInvoice::query()
            ->with(['payment', 'membership.plan', 'tenant.plan'])
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoice);

        return $this->organizationView('invoice-show', [
            'tenant'  => $tenant,
            'invoice' => $invoiceModel,
            'payment' => $invoiceModel->payment,
        ]);
    }

    public function downloadPdf(int $invoice, TenantInvoicePdfService $pdfs)
    {
        $tenant = $this->organizationTenant();

        $invoiceModel = TenantInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoice);

        return $pdfs->download($invoiceModel);
    }
}
