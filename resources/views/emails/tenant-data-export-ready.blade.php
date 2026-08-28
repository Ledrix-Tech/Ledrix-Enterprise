@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Workspace export ready')

@section('content')
    <h2 class="email-heading">Your workspace export is ready</h2>

    <p>Hi {{ $tenant->name }},</p>

    <p>
        The GDPR / workspace data package for <strong>{{ $tenant->name }}</strong>
        is ready to download. The ZIP is available for
        <strong>{{ \App\Services\Tenant\TenantDataExportService::TENANT_LINK_HOURS }} hours</strong>
        from the Organization portal.
    </p>

    @if ($export->expires_at)
        <p>Download before <strong>{{ $export->expires_at->format('M d, Y g:i A') }}</strong>.</p>
    @endif

    <a href="{{ $exportUrl }}" class="email-btn">Download export</a>

    <p class="email-muted" style="margin-top:24px;">
        Sign in as a workspace admin to download. If you did not request this export, contact support.
    </p>
@endsection
