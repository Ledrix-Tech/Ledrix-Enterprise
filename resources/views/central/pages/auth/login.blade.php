@extends('central.layout.auth')

@section('title', 'Super Admin | Login')

@section('auth-content')
    <div class="sa-auth-page">
        <div class="sa-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="sa-auth-logo">
            <h1>Super Admin</h1>
            <p class="sa-auth-sub">Sign in to manage the platform</p>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif

            <form action="{{ route('super-admin.login.post') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="d-flex justify-content-end mb-3">
                    <a href="{{ route('super-admin.forgot.get') }}">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-submit">Sign in</button>
            </form>

            @if (app(\App\Services\Security\OidcClientService::class)->isEnabled())
                <div class="text-center my-3 text-muted small">or</div>
                <a href="{{ route('super-admin.sso.redirect') }}" class="btn btn-outline-secondary w-100">
                    Continue with SSO
                </a>
            @endif
        </div>
    </div>
@endsection
