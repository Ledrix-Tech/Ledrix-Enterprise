@if (! empty($isDemoSandbox))
    <div class="sandbox-tour-banner" role="status">
        <div class="sandbox-tour-banner__copy">
            <i class="bi bi-play-circle" aria-hidden="true"></i>
            <span>
                Shared <strong>Ledrix sandbox</strong> — sample data only.
                Start a real trial on a clean workspace; demo data will not come with you.
            </span>
        </div>
        <div class="sandbox-tour-banner__actions">
            <form method="POST" action="{{ route('sandbox.open', 'admin') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm {{ ($demoSandboxRole ?? '') === 'admin' ? 'btn-light' : 'btn-outline-light' }}">Admin</button>
            </form>
            <form method="POST" action="{{ route('sandbox.open', 'seller') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm {{ ($demoSandboxRole ?? '') === 'seller' ? 'btn-light' : 'btn-outline-light' }}">Seller</button>
            </form>
            <form method="POST" action="{{ route('sandbox.open', 'client') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm {{ ($demoSandboxRole ?? '') === 'client' ? 'btn-light' : 'btn-outline-light' }}">Client</button>
            </form>
            <a href="{{ route('pricing.get') }}" class="btn btn-sm btn-warning">Start a real trial</a>
            <form method="POST" action="{{ route('sandbox.logout') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light">Exit sandbox</button>
            </form>
        </div>
    </div>
    <style>
        .sandbox-tour-banner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem;
            background: #1e3a5f;
            color: #eff6ff;
            border-bottom: 1px solid #172554;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            z-index: 1040;
        }
        .sandbox-tour-banner__copy {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            max-width: 46rem;
        }
        .sandbox-tour-banner__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }
        .sandbox-tour-banner .btn-outline-light {
            color: #eff6ff;
            border-color: rgba(239, 246, 255, 0.45);
        }
        .sandbox-tour-banner .btn-warning {
            font-weight: 600;
        }
    </style>
@endif
