@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Billing notice')

@section('content')
    @php
        $graceDays = (int) config('subscription.past_due_grace_days', 7);
        $lockDate = $membership->past_due_at
            ? $membership->past_due_at->copy()->addDays($graceDays)
            : ($membership->end_date?->copy()->addDays($graceDays));
    @endphp

    <h2 class="email-heading">
        @if ($stepDays <= 0)
            We could not collect your subscription payment
        @elseif ($stepDays <= 3)
            Your CRM is in a billing grace period
        @else
            Final notice before CRM access ends
        @endif
    </h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Your Ledrix {{ $tenant->plan?->name ?? 'plan' }} subscription is
        <strong>past due</strong>.
        @if ($stepDays <= 0)
            Please update your payment method or pay the open invoice so your team keeps CRM access.
        @elseif ($stepDays <= 3)
            Admin and Seller CRM stay available during a {{ $graceDays }}-day grace window.
            Pay now to avoid interruption.
        @else
            This is the last reminder before Admin and Seller CRM lock.
            Pay today to restore a normal subscription.
        @endif
    </p>

    @if ($lockDate)
        <p>Grace period ends on <strong>{{ $lockDate->format('M d, Y') }}</strong>.</p>
    @endif

    <a href="{{ route('tenant.billing') }}" class="email-btn">Update billing</a>

    <p class="email-muted" style="margin-top:24px;">
        Already paid? Allow a few minutes for Stripe to confirm, or reply to this email.
    </p>
@endsection
