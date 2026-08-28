@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Workspace suspended')

@section('content')
    <h2 class="email-heading">Your workspace has been suspended</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Access to the Ledrix CRM for <strong>{{ $tenant->name }}</strong> is paused
        until this is reviewed or resolved.
    </p>

    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="text-align:left;background:#f9fafb;border-radius:8px;padding:8px 12px;">
        <tr>
            <td width="160"><strong>Reason</strong></td>
            <td>{{ $reason }}</td>
        </tr>
        @if ($tenant->suspended_at)
            <tr>
                <td><strong>Suspended</strong></td>
                <td>{{ $tenant->suspended_at->format('M d, Y g:i A') }}</td>
            </tr>
        @endif
    </table>

    <a href="{{ $billingUrl }}" class="email-btn">Open billing</a>

    <p class="email-muted" style="margin-top:24px;">
        If you believe this is a mistake, reply to this email or contact Ledrix support.
    </p>
@endsection
