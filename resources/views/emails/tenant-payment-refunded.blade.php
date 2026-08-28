@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Refund recorded')

@section('content')
    @php
        $tenant = $payment->tenant;
        $invoice = $payment->invoice;
        $currency = strtoupper($payment->currency ?? 'USD');
        $decimals = $currency === 'USD' ? 2 : 0;
    @endphp

    <h2 class="email-heading">Refund recorded</h2>

    <p>Hi {{ $tenant->name ?? 'there' }},</p>

    @if ($stripeRefunded)
        <p>We refunded <strong>{{ $currency }} {{ number_format($refundAmount, $decimals) }}</strong> to your original payment method. Banks can take a few business days to show the credit.</p>
    @elseif ($creditApplied > 0)
        <p>We credited <strong>{{ $currency }} {{ number_format($creditApplied, $decimals) }}</strong> to your Ledrix billing balance. It will apply on your next invoice.</p>
    @else
        <p>A refund of <strong>{{ $currency }} {{ number_format($refundAmount, $decimals) }}</strong> was recorded on your account.</p>
    @endif

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;background:#f9fafb;border-radius:8px;padding:8px 12px;">
        @if ($invoice)
            <tr>
                <td width="160"><strong>Invoice</strong></td>
                <td>{{ $invoice->invoice_number ?: '#'.$invoice->id }}</td>
            </tr>
        @endif
        <tr>
            <td width="160"><strong>Refund</strong></td>
            <td>{{ $currency }} {{ number_format($refundAmount, $decimals) }}</td>
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
        If you did not expect this refund, reply to this email.
    </p>
@endsection
