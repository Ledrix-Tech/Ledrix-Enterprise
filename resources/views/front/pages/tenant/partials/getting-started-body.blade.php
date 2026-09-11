@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
<main class="{{ $isAdminOrg ? 'pb-4' : 'py-5' }}">
    <div class="{{ $isAdminOrg ? '' : 'container' }}" style="max-width: 720px;">
        @if ($isAdminOrg)
            <div class="crm-page-header mb-3">
                <div>
                    <h1>Getting started</h1>
                    <p>{{ $intro }}</p>
                </div>
            </div>
        @else
            <div class="mb-4">
                <a href="{{ org_route('dashboard') }}" class="text-muted small text-decoration-none">&larr; Dashboard</a>
                <h4 class="mb-1 mt-1">Getting started</h4>
                <p class="text-muted small mb-0">{{ $intro }}</p>
            </div>
        @endif

        <div class="gs-guide-help-card">
            <div class="gs-guide-body" style="padding: 1rem; border-radius: 16px;">
                @include('getting-started.steps', ['steps' => $steps, 'compact' => false])
                <p class="gs-guide-more mb-0">
                    Need more detail on any section?
                    <a href="{{ org_route('support.index') }}">Open Support</a>
                    if something in this sequence does not match what you see.
                </p>
            </div>
        </div>
    </div>
</main>
