@extends('front.layout.layout')

@section('title', 'Pricing')

@section('seo_title', 'Ledrix CRM Pricing — Plans for Closers & Agencies (No Card)')
@section('meta_description', 'Compare Ledrix CRM plans for closers and agencies. Free trial with a real seller panel, lead routing, and payment links. No credit card required.')
@section('meta_keywords', 'Ledrix pricing, sales CRM pricing, closer CRM plans, CRM free trial, seller panel, payment links CRM, multi-brand CRM pricing')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Pricing', 'url' => route('pricing.get')],
    ]])
    @if ($packages->isNotEmpty())
        <script type="application/ld+json">
        {!! json_encode([
            '@'.'context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'Ledrix CRM Subscription',
            'description' => 'Sales CRM plans for founders and agencies — lead routing, seller panels, multi-brand pipelines, payment links, and per-plan free trial.',
            'brand' => ['@type' => 'Brand', 'name' => 'Ledrix'],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'USD',
                'lowPrice' => (string) ($packages->min('monthly_price') ?: 0),
                'highPrice' => (string) ($packages->max('monthly_price') ?: 0),
                'offerCount' => $packages->count(),
                'offers' => $packages->map(fn ($p) => [
                    '@type' => 'Offer',
                    'name' => $p->name ?? 'Plan',
                    'price' => (string) ($p->monthly_price ?? 0),
                    'priceCurrency' => 'USD',
                    'url' => route('tenant.register.form', $p->slug),
                    'availability' => 'https://schema.org/InStock',
                ])->values()->all(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
    @include('front.includes.schema-faq', ['faqs' => config('seo.pricing_faq', [])])
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/pricing.css') }}">
@endpush

@section('main-content')
    @php
        $formatLimit = fn ($value) => (int) $value === -1 ? 'Unlimited' : number_format((int) $value);
        $minPrice = $packages->isNotEmpty() ? $packages->min('monthly_price') : null;
        $featured = $packages->firstWhere('is_popular', true) ?? $packages->first();
    @endphp

    <div class="pricing-page">
        {{-- Hero --}}
        <section class="pricing-hero text-center" aria-labelledby="pricing-hero-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 pricing-hero-inner">
                        <span class="pricing-hero-badge"><i class="bi bi-lightning-charge"></i> Pick a plan after you see the demo</span>
                        <h1 id="pricing-hero-heading">Pricing that plugs sales leaks — not another CRM bill</h1>
                        <p class="pricing-hero-lead">
                            @if ($minPrice !== null)
                                Plans from ${{ number_format($minPrice, $minPrice == floor($minPrice) ? 0 : 2) }}/month.
                            @endif
                            Open a live workspace with lead routing, seller panels, and payment links —
                            @if ($trialLabelGeneric === 'plan-based free trial')
                                each plan has its own free trial,
                            @else
                                {{ $trialLabelGeneric }} included,
                            @endif
                            no credit card. Questions? <a href="{{ route('faq.get') }}">FAQ</a> or <a href="{{ route('contact-us.get') }}">talk to sales</a>.
                        </p>

                        <div class="pricing-trust-row mb-4">
                            <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                            <span><i class="bi bi-buildings"></i> Multi-brand, one login</span>
                            <span><i class="bi bi-arrow-repeat"></i> Cancel anytime</span>
                            <span><i class="bi bi-unlock"></i> Full CRM in trial</span>
                        </div>

                        @if ($packages->isNotEmpty())
                            <div class="pricing-billing-toggle">
                                <label data-billing-label="monthly" class="active">Monthly</label>
                                <label class="pricing-switch">
                                    <input type="checkbox" id="pricing-billing-toggle" aria-label="Toggle yearly billing">
                                    <span class="pricing-switch-slider"></span>
                                </label>
                                <label data-billing-label="yearly">Yearly</label>
                                <span class="pricing-save-badge">Save with annual</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Value strip --}}
        @if ($packages->isNotEmpty())
        <section class="pricing-value-strip" aria-label="Pricing benefits">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="pricing-value-grid">
                            <div class="pricing-value-item">
                                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                <span>Live workspace in minutes</span>
                            </div>
                            <div class="pricing-value-item">
                                <i class="bi bi-layers" aria-hidden="true"></i>
                                <span>Multiple brands, one bill</span>
                            </div>
                            <div class="pricing-value-item">
                                <i class="bi bi-sliders" aria-hidden="true"></i>
                                <span>Pick the plan that fits</span>
                            </div>
                            <div class="pricing-value-item">
                                <i class="bi bi-arrow-up-circle" aria-hidden="true"></i>
                                <span>Upgrade when revenue grows</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif

        {{-- Plan cards --}}
        <section class="pricing-cards-section" aria-labelledby="pricing-plans-heading">
            <div class="container">
                @if ($packages->isEmpty())
                    <div class="pricing-empty">
                        <i class="bi bi-inbox display-6 d-block mb-3 text-secondary"></i>
                        <h4 class="fw-bold text-dark">No plans published yet</h4>
                        <p class="mb-0">Check back soon or <a href="{{ route('contact-us.get') }}">contact us</a> for enterprise pricing.</p>
                    </div>
                @else
                    <h2 class="visually-hidden" id="pricing-plans-heading">Ledrix CRM pricing plans</h2>
                    <div class="row g-4 justify-content-center align-items-stretch pricing-cards-row">
                        @foreach ($packages as $index => $package)
                            <div class="col-md-6 col-lg-4 d-flex">
                                <article class="pricing-card {{ $package->is_popular ? 'is-popular' : '' }}">
                                    @if ($package->badge_text)
                                        <div class="pricing-card-badge">{{ $package->badge_text }}</div>
                                    @elseif ($package->is_popular)
                                        <div class="pricing-card-badge">Most popular</div>
                                    @endif

                                    <div class="pricing-card-body">
                                        <div class="pricing-card-header">
                                            <div class="pricing-card-name">{{ $package->name }}</div>
                                            @if ($package->description)
                                                <p class="pricing-card-desc mb-0">{{ $package->description }}</p>
                                            @endif
                                        </div>

                                        <div class="pricing-card-price"
                                            data-price-monthly="{{ $package->monthly_price }}"
                                            data-price-yearly="{{ $package->yearly_price ?: $package->monthly_price * 12 }}">
                                            <span class="amount">${{ number_format($package->monthly_price, $package->monthly_price == floor($package->monthly_price) ? 0 : 2) }}</span>
                                            <span class="period">/month</span>
                                        </div>

                                        @if ((int) $package->trial_days > 0)
                                            <div class="pricing-trial-pill">
                                                <i class="bi bi-gift"></i>
                                                {{ (int) $package->trial_days }}-day free trial
                                            </div>
                                        @endif

                                        <div class="pricing-limits-panel">
                                            <p class="pricing-limits-label">Workspace limits</p>
                                            <div class="pricing-limits-row">
                                                <span class="pricing-limit-chip"><i class="bi bi-people"></i> {{ $formatLimit($package->max_sellers) }} sellers</span>
                                                <span class="pricing-limit-chip"><i class="bi bi-funnel"></i> {{ $formatLimit($package->max_leads_per_month) }} leads/mo</span>
                                                <span class="pricing-limit-chip"><i class="bi bi-building"></i> {{ $formatLimit($package->max_brands) }} brands</span>
                                                <span class="pricing-limit-chip"><i class="bi bi-shield"></i> {{ $formatLimit($package->max_admins) }} admins</span>
                                                <span class="pricing-limit-chip"><i class="bi bi-person-badge"></i> {{ $formatLimit($package->max_clients) }} clients</span>
                                                <span class="pricing-limit-chip"><i class="bi bi-hdd"></i> {{ $formatLimit($package->max_storage_mb) }} MB</span>
                                            </div>
                                        </div>

                                        @php
                                            $enabledFeatures = collect($featureRows)
                                                ->filter(fn ($label, $key) => (bool) $package->{$key})
                                                ->values();
                                        @endphp
                                        @if ($enabledFeatures->isNotEmpty())
                                            <div class="pricing-features-panel">
                                                <p class="pricing-features-label">Included modules</p>
                                                <ul class="pricing-features pricing-features-auto mb-0">
                                                    @foreach ($enabledFeatures->take(6) as $label)
                                                        <li><i class="bi bi-check2"></i> {{ $label }}</li>
                                                    @endforeach
                                                </ul>
                                                @if ($enabledFeatures->count() > 6)
                                                    <p class="pricing-features-more mb-0">+ {{ $enabledFeatures->count() - 6 }} more in comparison table</p>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="pricing-card-cta">
                                            <a href="{{ route('tenant.register.form', array_filter(['slug' => $package->slug, 'ref' => request('ref')])) }}"
                                                class="btn w-100 {{ $package->is_popular ? 'btn-primary pricing-btn' : 'pricing-btn pricing-btn-outline' }}">
                                                @if ((int) $package->trial_days > 0)
                                                    Start {{ (int) $package->trial_days }}-day free trial
                                                @else
                                                    Get started
                                                @endif
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-center text-muted small mt-4 mb-0">
                        <a href="{{ route('tenant.login') }}">Already have an account?</a>
                        · <a href="{{ route('features.get') }}">Explore all CRM features</a>
                    </p>
                @endif
            </div>
        </section>

        {{-- Comparison table --}}
        @if ($packages->isNotEmpty())
            <section class="pricing-compare-section" aria-labelledby="pricing-compare-heading">
                <div class="container">
                    <div class="pricing-compare-header text-center">
                        <span class="pricing-compare-kicker">Full comparison</span>
                        <h2 id="pricing-compare-heading">Compare plans side by side</h2>
                        <p class="text-muted mb-0">Usage limits and modules for every package — choose the plan that matches your team size and sales workflow.</p>
                    </div>

                    <div class="pricing-compare-panel">
                        <div class="pricing-compare-wrap">
                            <table class="table pricing-compare-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="pricing-compare-feature-col">What's included</th>
                                        @foreach ($packages as $package)
                                            <th class="pricing-compare-plan-col">
                                                <div class="pricing-compare-plan-name">{{ $package->name }}</div>
                                                <div class="pricing-compare-plan-price">${{ number_format($package->monthly_price, $package->monthly_price == floor($package->monthly_price) ? 0 : 2) }}/mo</div>
                                                @if ((int) $package->trial_days > 0)
                                                    <div class="pricing-compare-plan-trial">{{ (int) $package->trial_days }}-day trial</div>
                                                @endif
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="pricing-compare-group">
                                        <td colspan="{{ $packages->count() + 1 }}">Usage limits</td>
                                    </tr>
                                    @foreach ($limitRows as $key => $label)
                                        <tr>
                                            <td class="feature-label">{{ $label }}</td>
                                            @foreach ($packages as $package)
                                                <td><span class="limit-val">{{ $formatLimit($package->$key) }}</span></td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td class="feature-label">Sheet import</td>
                                        @foreach ($packages as $package)
                                            <td><span class="limit-val">{{ $package->sheetImportComparisonLabel() }}</span></td>
                                        @endforeach
                                    </tr>

                                    <tr class="pricing-compare-group">
                                        <td colspan="{{ $packages->count() + 1 }}">Modules &amp; features</td>
                                    </tr>
                                    @foreach ($featureRows as $key => $label)
                                        <tr>
                                            <td class="feature-label">{{ $label }}</td>
                                            @foreach ($packages as $package)
                                                <td>
                                                    @if ($package->$key)
                                                        <span class="check-yes" aria-label="Included"><i class="bi bi-check-circle-fill"></i></span>
                                                    @else
                                                        <span class="check-no" aria-label="Not included"><i class="bi bi-dash-circle"></i></span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @include('front.includes.pricing-faq-section')

        {{-- Bottom CTA --}}
        @if ($packages->isNotEmpty() && $featured)
            <section class="pricing-cta-band" aria-labelledby="pricing-cta-heading">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <h2 id="pricing-cta-heading">Ready to stop dropping leads?</h2>
                            <p>Watch the demo, then open {{ $featured->name ?? 'a plan' }} live — no credit card. Route leads, open a seller panel, send a payment link.</p>
                            <div class="d-flex flex-wrap gap-3 justify-content-center">
                                <a href="{{ route('tenant.register.form', array_filter(['slug' => $featured->slug, 'ref' => request('ref')])) }}" class="btn btn-light btn-lg fw-bold px-4">
                                    {{ $trialStartCtaOnFeatured }}
                                </a>
                                <a href="{{ route('contact-us.get') }}" class="btn btn-outline-light btn-lg px-4">Talk to sales</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front-assets/js/pricing.js') }}" defer></script>
@endpush
