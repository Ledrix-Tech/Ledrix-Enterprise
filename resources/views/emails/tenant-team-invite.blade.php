@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Workspace invitation')

@section('content')
    <h2 class="email-heading">You have been added to {{ $tenant->name }}</h2>

    <p>Hi {{ $memberName }},</p>

    <p>
        You now have a <strong>{{ $roleLabel }}</strong> seat on the
        <strong>{{ $tenant->name }}</strong> Ledrix workspace.
    </p>

    <a href="{{ $loginUrl }}" class="email-btn">Sign in to admin</a>

    <p class="email-muted" style="margin-top:24px;">
        Your workspace administrator set a password for this account.
        Use the email address this message was sent to. If you were not given the password,
        choose <strong>Forgot password</strong> on the admin login page.
        Do not share this login with anyone outside your team.
    </p>
@endsection
