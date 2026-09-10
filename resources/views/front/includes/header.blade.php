<!-- Header -->
{{-- <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm"> --}}
<nav class="navbar navbar-expand-lg shadow-sm smart-navbar" aria-label="Primary navigation">

    <div class="container">
        <a class="navbar-brand" href="{{ route('index.get') }}">
            <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}"
                alt="Ledrix CRM — multi-tenant CRM for agencies running multiple client brands">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-lg-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('features.get') }}">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('pricing.get') }}">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('about.get') }}">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('faq.get') }}">FAQ</a></li>
                <li class="nav-item">
                    <a href="{{ route('contact-us.get') }}" class="nav-link">Contact</a>
                </li>
            </ul>
            <div class="nav-cta">
                @if (auth()->guard('tenant')->check())
                    <a href="{{ route('tenant.dashboard') }}" class="nav-cta-signin">Profile</a>
                @else
                    <a href="{{ route('tenant.login') }}" class="nav-cta-signin">Sign in</a>
                @endif
                <a href="{{ route('sandbox.register') }}" class="btn nav-cta-demo">Demo</a>
                <a href="{{ route('pricing.get') }}" class="btn nav-cta-start">Get started free</a>
            </div>
        </div>
    </div>
</nav>