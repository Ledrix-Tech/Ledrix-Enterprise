@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Invoice voided')

@section('content')
    @php
        $tenant = $invoice->tenant;
        $currency = strtoupper($invoice->currency ?? 'USD');
        $decimals = $currency === 'USD' ? 2 : 0;
        $amount = (float) ($invoice->total_amount ?? $invoice->amount ?? 0);
    @endphp

    <h2 class="email-heading">Invoice voided</h2>

    <p>Hi {{ $tenant->name ?? 'there' }},</p>

    <p>The invoice below is no longer payable. You do not need to send payment for it.</p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;background:#f9fafb;border-radius:8px;padding:8px 12px;">
        <tr>
            <td width="160"><strong>Invoice</strong></td>
            <td>{{ $invoice->invoice_number ?: '#'.$invoice->id }}</td>
        </tr>
        @if ($invoice->plan_name)
            <tr>
                <td><strong>Plan</strong></td>
                <td>{{ $invoice->plan_name }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Amount</strong></td>
            <td>{{ $currency }} {{ number_format($amount, $decimals) }}</td>
        </tr>
        @if ($reason)
            <tr>
                <td><strong>Reason</strong></td>
                <td>{{ $reason }}</td>
            </tr>
        @endif
    </table>

    <a href="{{ $billingUrl }}" class="email-btn">View billing</a>

    <p class="email-muted" style="margin-top:24px;">
        Questions about this change? Reply to this email and we will help.
    </p>
@endsection
