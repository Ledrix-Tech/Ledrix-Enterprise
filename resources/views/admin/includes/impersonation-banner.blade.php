@php
    $impersonating = session()->has('impersonator_super_admin_id');
@endphp
@if ($impersonating)
    @php
        $impersonatedTenantId = (int) session('impersonated_tenant_id');
        $impersonatedTenant = $impersonatedTenantId
            ? \App\Models\Central\Tenant::query()->find($impersonatedTenantId)
            : null;
        $startedAt = session('impersonation_started_at');
    @endphp
    <div class="crm-impersonation-banner" role="status">
        <div class="crm-impersonation-banner__copy">
            <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
            <span>
                Impersonating
                <strong>{{ $impersonatedTenant?->name ?? 'tenant' }}</strong>
                CRM admin
                @if ($startedAt)
                    <span class="crm-impersonation-banner__meta">· started {{ \Illuminate\Support\Carbon::parse($startedAt)->diffForHumans() }}</span>
                @endif
            </span>
        </div>
        <form method="POST" action="{{ route('admin.impersonation.stop') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-light">
                Exit impersonation
            </button>
        </form>
    </div>
    <style>
        .crm-impersonation-banner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem;
            background: #92400e;
            color: #fffbeb;
            border-bottom: 1px solid #78350f;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            z-index: 1040;
        }
        .crm-impersonation-banner__copy {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .crm-impersonation-banner__meta {
            opacity: 0.85;
            font-weight: 400;
        }
        .crm-impersonation-banner .btn-light {
            border: 0;
            font-weight: 600;
        }
    </style>
@endif
