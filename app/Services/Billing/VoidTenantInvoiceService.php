<?php

namespace App\Services\Billing;

use App\Mail\TenantInvoiceVoidedMail;
use App\Models\Central\AuditLog;
use App\Models\Central\TenantInvoice;
use App\Support\SafeMail;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class VoidTenantInvoiceService
{
    public function void(TenantInvoice $invoice, ?string $reason = null): TenantInvoice
    {
        if ($invoice->status !== 'issued') {
            throw new RuntimeException('Only issued (unpaid) invoices can be voided.');
        }

        $invoice->loadMissing('payment');

        $invoice->update([
            'status' => 'void',
            'notes'  => trim(($invoice->notes ?? '').' Voided'.($reason ? ': '.$reason : '').'.'),
        ]);

        if ($invoice->payment && $invoice->payment->status === 'pending') {
            $invoice->payment->update([
                'status'  => 'failed',
                'payload' => array_merge($invoice->payment->payload ?? [], [
                    'voided_at'   => now()->toDateTimeString(),
                    'void_reason' => $reason,
                ]),
            ]);
        }

        $actor = Auth::guard('super_admin')->user();
        AuditLog::record(
            action: 'subscription.invoice_voided',
            tenantId: (int) $invoice->tenant_id,
            actorType: $actor ? 'super_admin' : 'system',
            actorId: $actor?->id,
            actorName: $actor?->name ?? 'System',
            context: [
                'subject_type' => 'tenant_invoice',
                'subject_id'   => $invoice->id,
                'description'  => 'Invoice voided',
                'after'        => ['reason' => $reason],
            ]
        );

        $invoice = $invoice->fresh(['payment', 'tenant']) ?? $invoice;
        SafeMail::send(
            $invoice->tenant?->email,
            fn () => new TenantInvoiceVoidedMail($invoice, $reason),
            'Tenant invoice voided mail',
            ['invoice_id' => $invoice->id, 'tenant_id' => $invoice->tenant_id],
        );

        return $invoice;
    }
}
