@extends('front.layout.layout')

@section('title', 'Security & Compliance')

@section('seo_title', 'Security & Compliance — Ledrix CRM')
@section('meta_description', 'How Ledrix isolates workspace data, handles GDPR requests, and what we do — and do not — claim about compliance.')
@section('meta_keywords', 'Ledrix security, Ledrix GDPR, CRM data isolation, tenant isolation, data export, data erasure')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Security & Compliance', 'url' => route('security.get')],
    ]])
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    @php
        $company = config('seo.organization.name', 'Ledrix');
        $email = config('seo.organization.email', 'hello@ledrix.co');
        $updated = 'September 3, 2026';
    @endphp

    <div class="mkt-page mkt-page-legal">
        <section class="mkt-legal-hero">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <nav class="mkt-legal-crumbs" aria-label="Breadcrumb">
                            <a href="{{ route('index.get') }}">Home</a>
                            <span aria-hidden="true">/</span>
                            <span>Security &amp; Compliance</span>
                        </nav>
                        <div class="mkt-legal-hero-row">
                            <div>
                                <span class="mkt-legal-kicker"><i class="bi bi-shield-lock"></i> Reassurance</span>
                                <h1>Security &amp; compliance</h1>
                                <p class="mkt-legal-lead">What Ledrix actually does with your data. No flourish. No enterprise pitch.</p>
                            </div>
                            <div class="mkt-legal-updated">
                                <span class="mkt-legal-updated-label">Last updated</span>
                                <strong>{{ $updated }}</strong>
                            </div>
                        </div>
                        <div class="mkt-legal-switch" role="tablist" aria-label="Legal documents">
                            <a href="{{ route('terms.get') }}">Terms of Service</a>
                            <a href="{{ route('privacy.get') }}">Privacy Policy</a>
                            <a href="{{ route('security.get') }}" class="is-active" aria-current="page">Security</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mkt-legal-body">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <article class="mkt-legal">
                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">01</span> Workspace isolation</h2>
                                <p>
                                    Every company on Ledrix gets its own workspace. Leads, brands, sellers, clients, orders, and tickets
                                    are scoped to that workspace. Another organization on the platform cannot open yours.
                                </p>
                                <p>
                                    Inside a workspace, brands stay separate in the pipeline. Admins see the brands in <em>their</em> company — not every tenant on Ledrix.
                                    Sellers see their assigned book. Clients see only their own orders.
                                </p>
                                <p>
                                    Isolation is enforced in the application by tenant (and brand) scoping.
                                    We do not claim a dedicated physical server or a dedicated database for every customer.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">02</span> GDPR</h2>
                                <p>
                                    Ledrix is built to support GDPR requests. That is not the same as a third-party “GDPR certified” badge.
                                    We do not claim one.
                                </p>
                                <ul>
                                    <li>Your workspace data is isolated from other customers.</li>
                                    <li>Workspace owners can request a CSV export of their CRM and billing rows.</li>
                                    <li>You can ask us to access, correct, or erase personal data we hold about you.</li>
                                    <li>When you store your clients in Ledrix, you are the controller of that customer data. We process it to run the product.</li>
                                </ul>
                                <p>
                                    Privacy requests: <a href="mailto:{{ $email }}">{{ $email }}</a>.
                                    The legal basis and retention rules live in the
                                    <a href="{{ route('privacy.get') }}">Privacy Policy</a>.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">03</span> What we do not claim</h2>
                                <p>
                                    We do not claim SOC 2, ISO 27001, HIPAA, or any other third-party audit unless we publish it here with a date.
                                    If a buyer needs a signed DPA or a specific hosting region, ask
                                    <a href="{{ route('contact-us.get') }}">sales</a> — don’t assume it from this page.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">04</span> Practical security</h2>
                                <ul>
                                    <li>HTTPS in transit</li>
                                    <li>Hashed passwords</li>
                                    <li>Role-scoped portals (admin, seller, client)</li>
                                    <li>Optional two-factor authentication for admin and seller logins</li>
                                    <li>Workspace audit log for org owners</li>
                                </ul>
                                <p>No method of transmission or storage is 100% secure.</p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">05</span> SSO and SCIM</h2>
                                <p>
                                    Ledrix can sign CRM admins in through an OIDC identity provider, and can provision those admin accounts over SCIM 2.0.
                                    Both are turned on by us — there is no self-serve “connect Okta” screen in the workspace today.
                                </p>
                                <p>
                                    If you need SSO or SCIM for your team, ask
                                    <a href="{{ route('contact-us.get') }}">sales</a>.
                                    Do not assume it is live on every plan.
                                </p>
                            </div>

                            <div class="mkt-legal-section mkt-legal-section--last">
                                <h2><span class="mkt-legal-num">06</span> Contact</h2>
                                <p>
                                    {{ $company }} — <a href="mailto:{{ $email }}">{{ $email }}</a>
                                    · <a href="{{ route('contact-us.get') }}">Contact form</a>
                                    · <a href="{{ route('privacy.get') }}">Privacy Policy</a>
                                    · <a href="{{ route('faq.get') }}#faq-product">FAQ</a>
                                </p>
                            </div>
                        </article>

                        <div class="mkt-legal-footer-nav">
                            <a href="{{ route('privacy.get') }}" class="mkt-legal-next">
                                <span>Previous</span>
                                <strong><i class="bi bi-arrow-left"></i> Privacy Policy</strong>
                            </a>
                            <a href="{{ route('features.get') }}" class="mkt-legal-next">
                                <span>Product</span>
                                <strong>Features <i class="bi bi-arrow-right"></i></strong>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
