@extends('front.layout.layout')

@section('title', 'Security & Compliance')

@section('seo_title', 'Security & Compliance — Ledrix CRM')
@section('meta_description', 'How Ledrix isolates workspace data, handles GDPR requests, and the path we take toward formal compliance.')
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
        $updated = 'September 6, 2026';
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
                                <p class="mkt-legal-lead">How we protect workspace data today — and the path we take as your agency grows.</p>
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
                                    stay inside that workspace. Another organization on the platform cannot open yours.
                                </p>
                                <p>
                                    Inside a workspace, brands stay on their own pipeline. Admins see the brands in <em>their</em> company.
                                    Sellers see their assigned book. Clients see only their own orders.
                                </p>
                                <p>
                                    Isolation starts with tenant and brand scoping in the product — the path every workspace uses on day one.
                                    Agencies that need a dedicated CRM database can request that path as they grow; we plan it with you on a VPS when it fits the contract.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">02</span> GDPR path</h2>
                                <p>
                                    Ledrix is built so you can complete GDPR-style requests from the product and with our team — export, access, correction, and erasure.
                                    That working path is how we support compliance today, and we keep strengthening it as the platform grows.
                                </p>
                                <ul>
                                    <li>Your workspace data stays separate from other customers.</li>
                                    <li>Workspace owners can request a CSV export of their CRM and billing rows.</li>
                                    <li>You can ask us to access, correct, or erase personal data we hold about you.</li>
                                    <li>When you store your clients in Ledrix, you remain the controller of that customer data. We process it to run the product.</li>
                                </ul>
                                <p>
                                    Privacy requests: <a href="mailto:{{ $email }}">{{ $email }}</a>.
                                    Legal basis and retention live in the
                                    <a href="{{ route('privacy.get') }}">Privacy Policy</a>.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">03</span> Formal compliance path</h2>
                                <p>
                                    We are building toward recognized frameworks such as SOC 2 and ISO 27001.
                                    When an independent report is ready, we will publish it here with a date so you can share it with your buyers.
                                </p>
                                <p>
                                    If you need a signed DPA, a conversation about hosting region, or a security questionnaire for a larger deal,
                                    <a href="{{ route('contact-us.get') }}">talk to us</a> — we will walk the path with you.
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
                                <p>We keep tightening these controls and will post material updates on this page.</p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">05</span> SSO and SCIM</h2>
                                <p>
                                    Ledrix can sign CRM admins in through an OIDC identity provider, and can provision those admin accounts over SCIM 2.0.
                                    We enable that path with you when your team is ready — so directory login and user sync match how your IT already works.
                                </p>
                                <p>
                                    Ask
                                    <a href="{{ route('contact-us.get') }}">sales</a>
                                    to turn SSO or SCIM on for your workspace.
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
