@extends('front.layout.layout')

@section('title', 'Security & Compliance')

@section('seo_title', 'Security & Compliance — Ledrix CRM')
@section('meta_description', 'How Ledrix isolates each agency workspace, handles GDPR export and erasure with a logged reason, and sets up SSO for your team.')
@section('meta_keywords', 'Ledrix security, Ledrix GDPR, CRM data isolation, tenant isolation, data export, data erasure, SSO, audit log')

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
                                <span class="mkt-legal-kicker"><i class="bi bi-shield-lock"></i> For the agency owner</span>
                                <h1>Security &amp; compliance</h1>
                                <p class="mkt-legal-lead">What is live today so you can answer a client’s data question without scrambling — and how we set up SSO when your team is ready.</p>
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
                                    Every company on Ledrix gets a private workspace. Leads, brands, sellers, clients, orders, and tickets
                                    stay inside that workspace. Another organization on the platform cannot open yours.
                                </p>
                                <p>
                                    Inside the workspace, brands stay on their own pipeline. Admins see the brands in <em>their</em> company.
                                    Sellers see their assigned book. Clients see only their own orders.
                                </p>
                                <p>
                                    That isolation is live for every signup: the product scopes every CRM row to your tenant (and brand).
                                    Agencies that need a dedicated CRM database — a separate database for that workspace, not only a filter —
                                    can request it. We provision that database for the workspace when the contract calls for it.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">02</span> Who sees what</h2>
                                <p>
                                    Closers should not sit in admin tools. Finance should not run the pipeline. Clients should not see another buyer’s invoice.
                                    Ledrix encodes that in roles:
                                </p>
                                <ul>
                                    <li><strong>Admin</strong> — brands, sellers, keys, import, organization billing, team, export, audit log.</li>
                                    <li><strong>Seller / closer</strong> — their book, payment links, briefs, messages. They do not get Account Keys or raw Stripe/PayPal secrets. Card numbers stay on Stripe or PayPal’s checkout — not in the seller panel.</li>
                                    <li><strong>Finance</strong> — brand payment and payout reports only. The rest of the CRM is closed to that login.</li>
                                    <li><strong>Client</strong> — their orders, invoices, briefs, tickets, and messages.</li>
                                </ul>
                                <p>
                                    That is data minimization in the product: each login gets the records it needs to do the job, not the whole shop.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">03</span> GDPR — export and erasure (live)</h2>
                                <p>
                                    You remain the controller of the client data you store in Ledrix. We process it to run the product.
                                    When a client (or you, on their behalf) needs a copy or a deletion, the workspace already has a path:
                                </p>
                                <ul>
                                    <li><strong>Export (live).</strong> A workspace owner requests a ZIP of CRM and billing CSVs from Organization → Data export. The request requires a written reason. Super Admin prepares the file. You download it on a signed, time-limited link. That is what you hand a client when they ask “send me my data.”</li>
                                    <li><strong>Erasure (live).</strong> Super Admin can run a workspace erasure. The system requires a reason and writes that reason to the audit log. Access is revoked and personal fields are anonymized. Export first if you still need a copy.</li>
                                    <li><strong>Access and correction.</strong> Email <a href="mailto:{{ $email }}">{{ $email }}</a> for personal data we hold about you as a Ledrix customer. Legal basis and retention sit in the <a href="{{ route('privacy.get') }}">Privacy Policy</a>.</li>
                                </ul>
                                <p>
                                    The logged reason is the point: you can show who asked, why, and what ran — instead of hunting Slack when a client’s counsel writes.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">04</span> Audit trails (live)</h2>
                                <p>
                                    Two trails, for two jobs:
                                </p>
                                <ul>
                                    <li><strong>Lead view activity (Admin CRM dashboard).</strong> When a closer opens a lead, the workspace records seller, lead, brand, time, and IP. Admins see that feed on the dashboard and can clear it. That is how you answer “who looked at this lead, and when?”</li>
                                    <li><strong>Organization audit log.</strong> Organization → Audit log lists sensitive workspace actions: who did them, when, and a short description — data-export requests, team invites, domain changes, billing and plan events, 2FA, SSO sign-ins. Super Admin keeps a matching platform log (including impersonation).</li>
                                </ul>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">05</span> Practical security (live)</h2>
                                <ul>
                                    <li>HTTPS in transit</li>
                                    <li>Hashed passwords</li>
                                    <li>Role-scoped portals (admin, seller, finance, client)</li>
                                    <li>Optional two-factor authentication on admin and seller logins; platform owners can be required to enable it</li>
                                    <li>Workspace audit log as above</li>
                                </ul>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">06</span> Formal frameworks (roadmap)</h2>
                                <p>
                                    SOC 2 and ISO 27001 reports are not published on this page yet. When an independent report is ready, it will appear here with a date so you can send it to a buyer.
                                </p>
                                <p>
                                    For a signed DPA, a hosting-region conversation, or a security questionnaire on a larger deal,
                                    <a href="{{ route('contact-us.get') }}">use the contact form</a>. We will reply with the current pack — not a placeholder.
                                </p>
                            </div>

                            <div class="mkt-legal-section">
                                <h2><span class="mkt-legal-num">07</span> SSO and SCIM</h2>
                                <p>
                                    CRM admins can sign in through your identity provider (Okta, Microsoft Entra, or any OIDC IdP).
                                    Tell us the provider when you are ready. We turn SSO on for the workspace, give you the redirect URL and client settings, and your team uses “Sign in with SSO.”
                                </p>
                                <p>
                                    SCIM 2.0 is available on the same setup: your IdP can create, update, and deactivate CRM admin accounts at our SCIM Users API. We issue the bearer token to your IT contact.
                                </p>
                                <p>
                                    This is a guided setup, not a self-serve “Connect Okta” button in the trial. Ask
                                    <a href="{{ route('contact-us.get') }}">sales</a>
                                    and we walk the steps.
                                </p>
                            </div>

                            <div class="mkt-legal-section mkt-legal-section--last">
                                <h2><span class="mkt-legal-num">08</span> Next step</h2>
                                <p>
                                    {{ $company }} — <a href="mailto:{{ $email }}">{{ $email }}</a>
                                    · <a href="{{ route('contact-us.get') }}">Contact form</a> for DPA, SSO, or a dedicated database
                                    · <a href="{{ route('pricing.get') }}">Try a plan free</a>
                                    · <a href="{{ route('privacy.get') }}">Privacy Policy</a>
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
