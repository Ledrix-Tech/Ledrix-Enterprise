@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Payment Dispute')

@section('content')
    <p>Hello <strong>{{ $clientName }}</strong>,</p>

    <p>
        This is an update regarding a <strong>payment dispute / chargeback</strong>
        related to your order.
    </p>

    <hr class="email-divider">

    <h3 class="email-heading">Dispute status: {{ strtoupper($stageLabel) }}</h3>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;">
        <tr>
            <td width="140"><strong>Service</strong></td>
            <td>{{ $service }}</td>
        </tr>
        <tr>
            <td><strong>Brand</strong></td>
            <td>{{ $brandName }}</td>
        </tr>
        <tr>
            <td><strong>Order ID</strong></td>
            <td>#{{ $orderId }}</td>
        </tr>
        <tr>
            <td><strong>Provider</strong></td>
            <td>{{ ucfirst($provider) }}</td>
        </tr>
        <tr>
            <td><strong>Disputed amount</strong></td>
            <td>{{ $amount }}</td>
        </tr>
        @if ($reason)
            <tr>
                <td><strong>Details</strong></td>
                <td>{{ $reason }}</td>
            </tr>
        @endif
    </table>

    @if ($stage === 'created')
        <p class="email-muted">
            A dispute was filed. No seller commission has been deducted.
            None will be until the case is finally resolved against the agency and funds are withdrawn.
        </p>
    @elseif ($stage === 'updated')
        <p class="email-muted">
            The dispute is still open. No seller commission has been deducted.
        </p>
    @elseif ($stage === 'won')
        <p class="email-muted">
            The case was resolved in the agency’s favor. No seller commission was deducted.
        </p>
    @elseif ($stage === 'lost')
        <p class="email-muted">
            The case was resolved against the agency and funds were withdrawn.
            Seller commission for this payment has been deducted once.
        </p>
    @elseif ($stage === 'resolved')
        <p class="email-muted">
            This dispute has been marked as resolved by the bank or card network.
        </p>
    @endif
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        If you did not request this dispute or have any questions,
        please contact your seller or reply to this email immediately.<br><br>
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
