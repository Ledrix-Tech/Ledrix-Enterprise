@extends('front.layout.layout')

@section('hide_navbar', true)
@section('title', 'Try the Ledrix sandbox | Ledrix')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    <div class="auth-page">
        <div class="container-fluid p-0">
            <div class="row g-0 auth-shell">
                <div class="col-lg-5 auth-aside">
                    <div class="auth-aside-inner">
                        <a href="{{ route('index.get') }}" class="d-inline-block mb-4">
                            <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}" alt="Ledrix CRM logo" style="max-width:140px;height:auto;">
                        </a>
                        <span class="auth-brand-badge"><i class="bi bi-play-circle"></i> Shared sandbox</span>
                        <h1>Tour the full CRM flow</h1>
                        <p class="auth-aside-lead">See admin, seller, and client views with sample brands, leads, and payments. This is not a trial and does not open organization billing.</p>
                        <ul class="auth-feature-list">
                            <li><i class="bi bi-check-circle-fill"></i> Pre-seeded brands, closers, and a client portal</li>
                            <li><i class="bi bi-check-circle-fill"></i> Paid order on the right merchant + a lost dispute</li>
                            <li><i class="bi bi-check-circle-fill"></i> Trial stays separate — start one when you want a clean workspace</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-7 auth-main">
                    <div class="auth-card">
                        <div class="auth-card-header mb-4">
                            <h2>Create a sandbox login</h2>
                            <p>Name, email, and password only. You will land in the demo admin workspace — not a private agency account.</p>
                        </div>
                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                        @endif
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                        @endif
                        <form method="POST" action="{{ route('sandbox.register.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="auth-label" for="name">Your name</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-person auth-input-icon"></i>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                                        class="form-control @error('name') is-invalid @enderror"
                                        placeholder="Alex Rivera" required autofocus>
                                </div>
                                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="auth-label" for="email">Work email</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-envelope auth-input-icon"></i>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="you@company.com" required>
                                </div>
                                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="auth-label" for="company">Company <span class="text-muted fw-normal">(optional)</span></label>
                                <div class="auth-input-group">
                                    <i class="bi bi-building auth-input-icon"></i>
                                    <input type="text" id="company" name="company" value="{{ old('company') }}"
                                        class="form-control @error('company') is-invalid @enderror"
                                        placeholder="Your agency">
                                </div>
                                @error('company')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="auth-label" for="password">Password</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-lock auth-input-icon"></i>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="At least 8 characters" required>
                                    <button type="button" class="auth-input-toggle" data-toggle-password="#password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="auth-label" for="password_confirmation">Confirm password</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-lock auth-input-icon"></i>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" placeholder="Repeat password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary auth-btn-primary w-100">Enter the sandbox</button>
                        </form>
                        <p class="auth-footer-link mb-2">
                            Already have a sandbox login? <a href="{{ route('sandbox.login') }}">Sign in</a>
                        </p>
                        <p class="auth-footer-link mb-0">
                            Want a private workspace? <a href="{{ route('pricing.get') }}">Start a real trial</a> — demo data will not come with you.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front-assets/js/auth.js') }}" defer></script>
@endpush
