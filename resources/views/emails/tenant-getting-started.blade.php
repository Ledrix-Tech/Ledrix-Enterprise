@extends('emails.layouts.ledrix')

@section('title', 'Getting started with your Ledrix workspace')

@section('content')
    <h2 class="email-heading">Getting started</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>{{ $intro }}</p>

    <ol style="margin:16px 0 8px;padding-left:20px;text-align:left;">
        @foreach ($steps as $step)
            <li style="margin:0 0 10px;font-size:15px;line-height:1.45;">
                <strong>{{ $step['title'] }}</strong><br>
                <span style="color:#555;">{{ $step['body'] }}</span>
            </li>
        @endforeach
    </ol>

    <p class="email-muted">
        Need more detail on any section?
        After you sign in, open
        <a href="{{ $helpUrl }}" class="email-link">Getting started</a>
        from Help.
    </p>

    <a href="{{ $crmUrl }}" class="email-btn">Open your workspace</a>
@endsection
