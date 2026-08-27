@extends('central.layout.layout')

@section('title', 'Ledrix | Invoice '.$invoice->invoice_number)

@section('central-content')
    @php
        $currency = strtoupper($invoice->currency ?? 'USD');
        $decimals = $currency === 'USD' ? 2 : 0;
    @endphp

    <div class="sa-page-header">
        <div>
            <a href="{{ route('super-admin.tenant.show', $tenant->id) }}" class="text-muted small text-decoration-none">&larr; Tenant</a>
            <h1 class="mt-1">Invoice {{ $invoice->invoice_number }}</h1>
            <p>{{ $tenant->name }} · {{ ucfirst($invoice->status) }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('super-admin.tenant.invoice.pdf', [$tenant->id, $invoice->id]) }}" class="btn btn-sa-primary btn-sm">Download PDF</a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
            @if (($canManage ?? auth('super_admin')->user()?->isAdmin()) && $invoice->status === 'issued')
                <form method="POST" action="{{ route('super-admin.tenant.invoice.void', [$tenant->id, $invoice->id]) }}"
                    onsubmit="return confirm('Void this unpaid invoice?')">
                    @csrf
                    <input type="hidden" name="reason" value="Voided by super admin">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Void</button>
                </form>
            @endif
            @if (($canManage ?? auth('super_admin')->user()?->isAdmin()) && $payment?->status === 'paid')
                <form method="POST" action="{{ route('super-admin.tenant.invoice.refund', [$tenant->id, $invoice->id]) }}"
                    onsubmit="return confirm('Record a refund for this payment? Meezan/manual refunds credit the tenant balance; Stripe refunds the card when a PaymentIntent is stored.')">
                    @csrf
                    <input type="hidden" name="reason" value="Refunded by super admin">
                    <button type="submit" class="btn btn-outline-warning btn-sm">Refund</button>
                </form>
            @endif
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><strong>Plan</strong><br>{{ $invoice->plan_name ?? '—' }}</div>
                <div class="col-md-4"><strong>Cycle</strong><br>{{ ucfirst($invoice->billing_cycle ?? 'monthly') }}</div>
                <div class="col-md-4"><strong>Gateway</strong><br>{{ $payment ? ucfirst(str_replace('_', ' ', $payment->gateway)) : '—' }}</div>
                <div class="col-md-4"><strong>Issued</strong><br>{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</div>
                <div class="col-md-4"><strong>Due</strong><br>{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</div>
                <div class="col-md-4"><strong>Paid</strong><br>{{ $invoice->paid_at?->format('M d, Y H:i') ?? '—' }}</div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Ledrix CRM — {{ $invoice->plan_name ?? 'Subscription' }}</td>
                        <td class="text-end">{{ $currency }} {{ number_format((float) $invoice->amount, $decimals) }}</td>
                    </tr>
                    @if ((float) $invoice->tax_amount > 0)
                        <tr>
                            <td>{{ config('services.invoice_tax.label', 'Tax') }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format((float) $invoice->tax_amount, $decimals) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="text-end">{{ $currency }} {{ number_format((float) $invoice->total_amount, $decimals) }}</th>
                    </tr>
                </tfoot>
            </table>

            @if ($payment?->transaction_id)
                <p class="small text-muted mb-0">Reference: <code>{{ $payment->transaction_id }}</code></p>
            @endif
        </div>
    </div>
@endsection
