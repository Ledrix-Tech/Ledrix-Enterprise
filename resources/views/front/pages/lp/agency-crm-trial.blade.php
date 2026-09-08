@extends('front.layout.lp')

@section('title', 'Start free trial — no card')

@section('seo_title', 'Ledrix CRM Free Trial — No Card, Agency Workspace in Minutes')
@section('meta_description', 'Start a free Ledrix CRM trial with no credit card. Isolate client brands, route Stripe and PayPal to the right merchant, and keep closers in their assigned book.')
@section('robots', 'noindex, follow')

@section('main-content')
    <div class="mkt-page lp-page">
        <section class="mkt-hero text-center">
            <div class="container mkt-hero-inner px-3 px-sm-4">
                <span class="mkt-hero-badge"><i class="bi bi-lightning-charge-fill"></i> {{ $trialDays }}-day free trial — no card</span>
                <h1>Stop mixing client brands in one CRM</h1>
                <p class="mkt-hero-lead">
                    Isolate each client’s leads, generate Stripe and PayPal under that merchant, and keep commission-only closers out of the full database — no credit card required.
                </p>
                <div class="mkt-hero-actions">
                    <a href="{{ $registerUrl }}" class="btn btn-lg mkt-btn-primary" data-lp-cta="trial-hero">
                        Start {{ $trialDays }}-day free trial — no card
                        @if ($package)
                            <span class="opacity-75">· {{ $package->name }}</span>
                        @endif
                    </a>
                    <a href="{{ route('lp.demo') }}" class="btn btn-lg mkt-btn-ghost">Book a demo</a>
                </div>
                <div class="mkt-trust-row">
                    <span><i class="bi bi-credit-card-2-front"></i> No card required</span>
                    <span><i class="bi bi-shield-lock"></i> Each agency workspace isolated</span>
                    <span><i class="bi bi-shop"></i> Right merchant per brand</span>
                </div>
            </div>
        </section>

        <section class="mkt-section mkt-section-alt">
            <div class="container text-center px-3 px-sm-4">
                <h2 class="mkt-section-title">Built for agencies who cannot share a ledger</h2>
                <p class="mkt-section-lead">
                    Turn inbound demand into the right brand’s closer and the right merchant — without a spreadsheet dump.
                </p>
                <div class="mkt-grid-3 text-start">
                    <div class="mkt-card">
                        <div class="mkt-card-icon"><i class="bi bi-inbox"></i></div>
                        <h5>Leads that stay on the brand</h5>
                        <p>Ingest from web forms, scripts, and API — then route to that client’s closer, not a shared pile.</p>
                    </div>
                    <div class="mkt-card">
                        <div class="mkt-card-icon"><i class="bi bi-people"></i></div>
                        <h5>Seller + admin workspaces</h5>
                        <p>Admins see every brand; commission-only closers get assigned records only.</p>
                    </div>
                    <div class="mkt-card">
                        <div class="mkt-card-icon"><i class="bi bi-cash-coin"></i></div>
                        <h5>Paid on the right merchant</h5>
                        <p>Stripe and PayPal payment links, client portal, and dispute tracking on that client’s payment.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mkt-section">
            <div class="container px-3 px-sm-4" style="max-width: 720px;">
                <h2 class="mkt-section-title text-center">Common questions</h2>
                <div class="accordion mkt-faq mt-4" id="lpTrialFaq">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Do I need a credit card to start?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#lpTrialFaq">
                            <div class="accordion-body">
                                No. Start the {{ $trialDays }}-day trial free. You only pay if you continue after the trial.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Who is Ledrix for?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#lpTrialFaq">
                            <div class="accordion-body">
                                Agencies and sales teams in the US and UK that run multiple client brands — and are done stuffing them into one CRM and one payment account.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                How fast can I go live?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#lpTrialFaq">
                            <div class="accordion-body">
                                Create your workspace, verify email, and open Admin CRM — usually in a few minutes.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mkt-cta-band text-center">
            <div class="container px-3 px-sm-4">
                <h2>Ready to keep the next chargeback on the right client?</h2>
                <p>Start your {{ $trialDays }}-day Ledrix trial — no card — and onboard your first brand today.</p>
                <a href="{{ $registerUrl }}" class="btn btn-lg mkt-btn-primary" data-lp-cta="trial-footer">Start {{ $trialDays }}-day free trial — no card</a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-lp-cta]').forEach(function (el) {
        el.addEventListener('click', function () {
            if (typeof fbq === 'function') {
                fbq('trackCustom', 'LpCtaClick', { cta: el.getAttribute('data-lp-cta') });
            }
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ event: 'lp_cta_click', cta: el.getAttribute('data-lp-cta') });
        });
    });
</script>
@endpush
