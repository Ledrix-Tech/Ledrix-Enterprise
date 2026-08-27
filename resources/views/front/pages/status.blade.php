@extends('front.layout.layout')

@section('title', 'System Status')

@section('seo_title', 'System Status — Ledrix CRM')
@section('meta_description', 'Live Ledrix CRM platform status, component health, incidents, and SLA targets for Admin, Seller, Client, API, and billing.')
@section('meta_keywords', 'Ledrix status, CRM uptime, Ledrix SLA, system status page')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'System Status', 'url' => route('status.get')],
    ]])
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
    <style>
        .status-page { padding: 2.5rem 0 4rem; }
        .status-hero { margin-bottom: 2rem; }
        .status-hero h1 { font-size: clamp(1.75rem, 3vw, 2.4rem); margin: 0.35rem 0 0.5rem; }
        .status-pill {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600;
        }
        .status-pill.is-operational { background: #ecfdf5; color: #047857; }
        .status-pill.is-degraded, .status-pill.is-maintenance { background: #fffbeb; color: #b45309; }
        .status-pill.is-partial_outage, .status-pill.is-major_outage { background: #fef2f2; color: #b91c1c; }
        .status-grid { display: grid; gap: 0.75rem; }
        .status-row {
            display: flex; justify-content: space-between; align-items: center; gap: 1rem;
            padding: 0.9rem 1rem; border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 10px;
            background: rgba(255,255,255,0.7);
        }
        .status-dot {
            width: 0.55rem; height: 0.55rem; border-radius: 50%; display: inline-block; margin-right: 0.4rem;
        }
        .status-dot.operational { background: #10b981; }
        .status-dot.degraded, .status-dot.maintenance { background: #f59e0b; }
        .status-dot.partial_outage, .status-dot.major_outage { background: #ef4444; }
        .status-section { margin-top: 2.25rem; }
        .status-section h2 { font-size: 1.15rem; margin-bottom: 0.75rem; }
        .status-incident {
            border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 0.75rem;
        }
        .status-incident h3 { font-size: 1rem; margin: 0 0 0.35rem; }
        .status-meta { font-size: 0.8rem; color: #64748b; }
        .status-sla {
            margin-top: 2rem; padding: 1.25rem 1.35rem; border-radius: 12px;
            background: linear-gradient(135deg, rgba(67,56,202,0.06), rgba(14,165,233,0.06));
            border: 1px solid rgba(67,56,202,0.12);
        }
        .status-subscribe { margin-top: 1.5rem; max-width: 420px; }
    </style>
@endpush

@section('main-content')
    <div class="mkt-page status-page">
        <div class="container">
            <nav class="mkt-legal-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('index.get') }}">Home</a>
                <span aria-hidden="true">/</span>
                <span>System Status</span>
            </nav>

            <div class="status-hero">
                <span class="status-pill is-{{ $overall }}">{{ $overallLabel }}</span>
                <h1>Ledrix system status</h1>
                <p class="text-muted mb-0">Component health, active incidents, and our monthly availability target. This page is manually maintained — not a probe farm.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @unless ($ready)
                <div class="alert alert-warning">Status data is not available yet. Platform operators need to run central migrations.</div>
            @else
                <section class="status-section" aria-labelledby="components-heading">
                    <h2 id="components-heading">Components</h2>
                    <div class="status-grid">
                        @foreach ($components as $component)
                            <div class="status-row">
                                <div>
                                    <strong>{{ $component->name }}</strong>
                                    @if ($component->description)
                                        <div class="status-meta">{{ $component->description }}</div>
                                    @endif
                                </div>
                                <span>
                                    <span class="status-dot {{ $component->status }}"></span>
                                    {{ str_replace('_', ' ', $component->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="status-section" aria-labelledby="incidents-heading">
                    <h2 id="incidents-heading">Incidents</h2>
                    @forelse ($incidents as $incident)
                        <article class="status-incident">
                            <h3>{{ $incident->title }}</h3>
                            <div class="status-meta mb-2">
                                {{ ucfirst($incident->severity) }} · {{ ucfirst($incident->status) }}
                                @if ($incident->started_at)
                                    · Started {{ $incident->started_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                @endif
                                @if ($incident->resolved_at)
                                    · Resolved {{ $incident->resolved_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                @endif
                            </div>
                            @if ($incident->body)
                                <p class="mb-0">{{ $incident->body }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-muted mb-0">No incidents recorded recently.</p>
                    @endforelse
                </section>

                <div class="status-sla">
                    <h2 class="h5 mb-2">Availability target (SLA)</h2>
                    <p class="mb-1"><strong>{{ $sla['target'] }}</strong> monthly uptime target for core CRM surfaces.</p>
                    <p class="mb-0 small text-muted">{{ $sla['window'] }}. Questions: <a href="mailto:{{ $sla['support'] }}">{{ $sla['support'] }}</a></p>
                </div>

                <div class="status-subscribe">
                    <h2 class="h5 mb-2">Subscribe to updates</h2>
                    <form method="POST" action="{{ route('status.subscribe') }}" class="d-flex gap-2 flex-wrap">
                        @csrf
                        <input type="email" name="email" class="form-control" required maxlength="255"
                            placeholder="you@company.com" value="{{ old('email') }}" aria-label="Email for status updates">
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </form>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            @endunless
        </div>
    </div>
@endsection
