<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        .header { margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; }
        .text-end { text-align: right; }
        .meta td { border: 0; padding: 3px 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #e2e8f0; }
    </style>
</head>
<body>
@php
    $currency = strtoupper($invoice->currency ?? $payment?->currency ?? 'USD');
    $decimals = $currency === 'USD' ? 2 : 0;
@endphp

<div class="header">
    <h1>Invoice {{ $invoice->invoice_number }}</h1>
    <div class="muted">{{ config('app.name', 'Ledrix') }} · {{ ucfirst($invoice->status) }}</div>
</div>

<table class="meta">
    <tr>
        <td width="50%">
            <strong>Billed to</strong><br>
            {{ $tenant->name }}<br>
            <span class="muted">{{ $tenant->email }}</span>
        </td>
        <td width="50%">
            <strong>Plan:</strong> {{ $invoice->plan_name ?? $tenant->plan?->name ?? '—' }}<br>
            <strong>Cycle:</strong> {{ ucfirst($invoice->billing_cycle ?? 'monthly') }}<br>
            <strong>Issued:</strong> {{ $invoice->issued_at?->format('M d, Y') ?? '—' }}<br>
            <strong>Due:</strong> {{ $invoice->due_at?->format('M d, Y') ?? '—' }}
            @if ($invoice->paid_at)
                <br><strong>Paid:</strong> {{ $invoice->paid_at->format('M d, Y H:i') }}
            @endif
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>Description</th>
            <th class="text-end">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                Ledrix CRM — {{ $invoice->plan_name ?? 'Subscription' }}
                <br><span class="muted">{{ ucfirst($invoice->billing_cycle ?? 'monthly') }} subscription</span>
            </td>
            <td class="text-end">{{ $currency }} {{ number_format((float) $invoice->amount, $decimals) }}</td>
        </tr>
        @if ((float) $invoice->tax_amount > 0)
            <tr>
                <td>{{ $taxLabel ?? 'Tax' }}</td>
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
    <p class="muted" style="margin-top: 20px;">Payment reference: {{ $payment->transaction_id }}</p>
@endif

@if ($invoice->notes)
    <p class="muted">{{ $invoice->notes }}</p>
@endif
</body>
</html>
