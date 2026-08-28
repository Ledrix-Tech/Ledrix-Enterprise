@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Status incident')

@section('content')
    <h2 class="email-heading">
        @if ($event === 'resolved')
            Incident resolved
        @elseif ($event === 'updated')
            Incident updated
        @else
            New status incident
        @endif
    </h2>

    <p><strong>{{ $incident->title }}</strong></p>

    <p>
        Severity: {{ ucfirst($incident->severity) }}
        · Status: {{ ucfirst($incident->status) }}
    </p>

    @if ($incident->body)
        <p>{{ $incident->body }}</p>
    @endif

    <a href="{{ $statusUrl }}" class="email-btn">View status page</a>

    <p class="email-muted" style="margin-top:24px;">
        You receive this because you subscribed to Ledrix status updates.
        <a href="{{ $unsubscribeUrl }}">Unsubscribe</a>
    </p>
@endsection
