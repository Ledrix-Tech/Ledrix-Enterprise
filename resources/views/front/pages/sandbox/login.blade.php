@extends('front.layout.layout')

@section('hide_navbar', true)
@section('title', 'Sandbox sign in | Ledrix')

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
                        <h1>Continue the CRM tour</h1>
                        <p class="auth-aside-lead">This login only opens the shared demo workspace. It is not your trial or billing account.</p>
                    </div>
                </div>
                <div class="col-lg-7 auth-main">
                    <div class="auth-card">
                        <div class="auth-card-header mb-4">
                            <h2>Sandbox sign in</h2>
                            <p>Use the email you registered for the sandbox.</p>
                        </div>
                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                        @endif
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                        @endif
                        <form method="POST" action="{{ route('sandbox.login.post') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="auth-label" for="email">Email</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-envelope auth-input-icon"></i>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="you@company.com" required autofocus>
                                </div>
                                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="auth-label" for="password">Password</label>
                                <div class="auth-input-group">
                                    <i class="bi bi-lock auth-input-icon"></i>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="••••••••" required>
                                    <button type="button" class="auth-input-toggle" data-toggle-password="#password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary auth-btn-primary w-100">Enter the sandbox</button>
                        </form>
                        <p class="auth-footer-link mb-2">
                            New here? <a href="{{ route('sandbox.register') }}">Create a sandbox login</a>
                        </p>
                        <p class="auth-footer-link mb-0">
                            Need a private workspace? <a href="{{ route('pricing.get') }}">Start a real trial</a>
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
