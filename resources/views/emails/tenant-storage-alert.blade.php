@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Storage usage alert')

@section('content')
    <h2 class="email-heading">
        @if ($thresholdPercent >= 100)
            Storage limit reached
        @else
            Storage nearly full
        @endif
    </h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        Your workspace is using
        <strong>{{ number_format($usedMb) }} MB</strong>
        of
        <strong>{{ number_format($limitMb) }} MB</strong>
        ({{ $thresholdPercent }}%+ of your plan storage limit).
    </p>

    @if ($thresholdPercent >= 100)
        <p>
            New file uploads may be blocked until you free space or upgrade your plan.
        </p>
    @else
        <p>
            Consider removing unused files or upgrading your plan before you hit the limit.
        </p>
    @endif

    <a href="{{ route('admin.org.plan') }}" class="email-btn">View plan &amp; usage</a>

    <p class="email-muted" style="margin-top:24px;">Questions? Reply to this email and our team will help.</p>
@endsection
