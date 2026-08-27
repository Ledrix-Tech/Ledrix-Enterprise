@extends('front.layout.layout')

@section('title', 'Contact')

@section('seo_title', 'Contact Ledrix CRM — Sales & Support')
@section('meta_description', 'Contact the Ledrix CRM team for pricing, enterprise plans, demos, and onboarding help. We respond within one business day.')
@section('meta_keywords', 'Contact Ledrix, Ledrix CRM support, CRM demo, sales CRM contact, agency CRM inquiry')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Contact', 'url' => route('contact-us.get')],
    ]])
    @php
        $orgUrl = rtrim((string) (config('seo.site_url') ?: config('app.url')), '/');
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => 'Contact Ledrix CRM',
        'url' => route('contact-us.get'),
        'description' => 'Contact Ledrix CRM for sales, support, and enterprise onboarding.',
        'mainEntity' => [
            '@type' => 'Organization',
            'name' => config('seo.organization.name'),
            'url' => $orgUrl,
            'email' => config('seo.organization.email'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'email' => config('seo.organization.email'),
                'availableLanguage' => ['English'],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    @php
        $packages = $packages ?? collect();
        $orgEmail = config('seo.organization.email');
        $trialUrl = $popularPackage
            ? route('tenant.register.form', $popularPackage->slug)
            : route('pricing.get');
    @endphp

    <div class="mkt-page mkt-page-contact">
        {{-- Hero --}}
        <section class="mkt-contact-hero text-center" aria-labelledby="contact-hero-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 mkt-contact-hero-inner">
                        <span class="mkt-contact-hero-badge"><i class="bi bi-chat-dots"></i> Sales &amp; support</span>
                        <h1 id="contact-hero-heading">Talk to us about dropped leads, multi-brand chaos, or a demo</h1>
                        <p class="mkt-contact-hero-lead">
                            Pricing questions, agency onboarding, or want to see seller panels and payment links live?
                            Send a message — we typically reply within one business day. Or skip the wait and open a free workspace.
                        </p>
                        <div class="mkt-contact-hero-actions">
                            <a href="#contactForm" class="btn btn-lg mkt-btn-primary">Send a message</a>
                            @if ($popularPackage)
                                <a href="{{ $trialUrl }}" class="btn btn-lg mkt-btn-ghost">{{ $trialStartCtaPopular }}</a>
                            @else
                                <a href="{{ $trialUrl }}" class="btn btn-lg mkt-btn-ghost">{{ $trialStartCtaGeneric }}</a>
                            @endif
                        </div>
                        <div class="mkt-contact-hero-meta">
                            <span><i class="bi bi-clock"></i> Reply within 24 hours</span>
                            <span><i class="bi bi-shield-check"></i> Secure &amp; private</span>
                            <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Contact form --}}
        <section class="mkt-contact-shell">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="row g-4 align-items-start">
                            {{-- Form --}}
                            <div class="col-lg-7">
                                <div class="mkt-contact-card mkt-contact-form-card">
                                    <div class="mkt-contact-card-head">
                                        <div class="mkt-contact-card-icon"><i class="bi bi-envelope-paper"></i></div>
                                        <div>
                                            <h2>Talk to our team</h2>
                                            <p>Fill out the form and we'll get back to you within one business day.</p>
                                        </div>
                                    </div>

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0 small">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form id="contactForm" class="mkt-contact-form" action="{{ route('contact.store') }}" method="POST">
                                        @csrf

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="mkt-form-label" for="contact-name">Full name *</label>
                                                <input type="text" id="contact-name" name="name"
                                                    value="{{ old('name') }}"
                                                    class="form-control mkt-form-control @error('name') is-invalid @enderror"
                                                    placeholder="John Doe" required>
                                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="mkt-form-label" for="contact-company">Company name</label>
                                                <input type="text" id="contact-company" name="company"
                                                    value="{{ old('company') }}"
                                                    class="form-control mkt-form-control"
                                                    placeholder="Acme Agency">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="mkt-form-label" for="contact-email">Business email *</label>
                                                <input type="email" id="contact-email" name="email"
                                                    value="{{ old('email') }}"
                                                    class="form-control mkt-form-control @error('email') is-invalid @enderror"
                                                    placeholder="you@company.com" required>
                                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="mkt-form-label" for="phone">Phone / WhatsApp</label>
                                                <div class="mkt-phone-field">
                                                    <input type="tel" id="phone" name="phone"
                                                        value="{{ old('phone') }}"
                                                        class="form-control mkt-form-control"
                                                        data-phone-input
                                                        placeholder="Enter your phone number" autocomplete="tel">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="mkt-form-label" for="company-size">Company size</label>
                                                <select id="company-size" name="company_size" class="form-select mkt-form-control">
                                                    <option value="">Select</option>
                                                    @foreach (['1-10' => '1 – 10 employees', '11-50' => '11 – 50 employees', '51-200' => '51 – 200 employees', '200+' => '200+ employees'] as $val => $label)
                                                        <option value="{{ $val }}" @selected(old('company_size') === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="mkt-form-label" for="inquiry-type">Inquiry type *</label>
                                                <select id="inquiry-type" name="inquiry_type"
                                                    class="form-select mkt-form-control @error('inquiry_type') is-invalid @enderror" required>
                                                    @foreach (['demo' => 'Request a demo', 'pricing' => 'Pricing & trial', 'sales' => 'Sales inquiry', 'partnership' => 'Partnership', 'support' => 'Technical support', 'general' => 'General inquiry'] as $val => $label)
                                                        <option value="{{ $val }}" @selected(old('inquiry_type', 'demo') === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('inquiry_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-12">
                                                <label class="mkt-form-label" for="contact-message">Message *</label>
                                                <textarea id="contact-message" name="message" rows="5"
                                                    class="form-control mkt-form-control @error('message') is-invalid @enderror"
                                                    placeholder="Tell us about your business requirements..." required>{{ old('message') }}</textarea>
                                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary mkt-submit-btn">
                                                    <i class="bi bi-send me-1"></i> Send inquiry
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Sidebar --}}
                            <div class="col-lg-5 mkt-contact-sidebar">
                                <div class="mkt-contact-trial-card">
                                    <div class="mkt-contact-trial-icon"><i class="bi bi-rocket-takeoff"></i></div>
                                    <h3>Prefer self-serve?</h3>
                                    <p>Open a free workspace — no card, no sales call. See lead routing and payment links live.</p>
                                    <a href="{{ $trialUrl }}" class="btn btn-primary w-100">Start free trial</a>
                                </div>

                                <div class="mkt-contact-card">
                                    <h4 class="mkt-contact-sidebar-title"><i class="bi bi-stars"></i> Why Ledrix?</h4>
                                    <ul class="mkt-contact-feature-list">
                                        @foreach (['Leads claimed — not left in a pile', 'Multiple brands, one login', 'Payment link after the Zoom yes', 'Closers only see their book', 'Admin view across every brand', 'Client portal without extra tools'] as $item)
                                            <li><i class="bi bi-check-lg"></i> {{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="mkt-contact-card">
                                    <h4 class="mkt-contact-sidebar-title"><i class="bi bi-info-circle"></i> Contact information</h4>
                                    <div class="mkt-info-item">
                                        <div class="mkt-info-icon"><i class="bi bi-envelope"></i></div>
                                        <div>
                                            <strong>Email</strong>
                                            <a href="mailto:{{ $orgEmail }}">{{ $orgEmail }}</a>
                                        </div>
                                    </div>
                                    <div class="mkt-info-item mb-0">
                                        <div class="mkt-info-icon"><i class="bi bi-clock"></i></div>
                                        <div>
                                            <strong>Response time</strong>
                                            <span>Within 24 hours</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('head')
    @include('front.includes.phone-input-styles')
@endpush

@push('scripts')
    @include('front.includes.phone-input-assets')
@endpush
