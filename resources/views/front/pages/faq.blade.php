@extends('front.layout.layout')

@section('title', 'FAQ')

@section('seo_title', 'Ledrix CRM FAQ — Trials, Brands, Portals, Payments & Seller Panels')
@section('meta_description', 'Answers for founders and agencies: free trial, multi-brand pipelines, seller panels, client portal, payment links, notifications, and how Ledrix stops dropped leads.')
@section('meta_keywords', 'Ledrix FAQ, CRM FAQ, agency CRM questions, free trial CRM, multi-brand CRM, seller panel, payment links CRM')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'FAQ', 'url' => route('faq.get')],
    ]])
    @include('front.includes.schema-faq', ['faqs' => array_merge(config('seo.faq', []), config('seo.pricing_faq', []))])
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    @php
        $packages = $packages ?? collect();
        $minPrice = $packages->isNotEmpty() ? $packages->min('monthly_price') : null;
    @endphp

    <div class="mkt-page mkt-page-faq">
        {{-- Hero --}}
        <section class="mkt-faq-hero text-center" aria-labelledby="faq-hero-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 mkt-faq-hero-inner">
                        <span class="mkt-faq-hero-badge"><i class="bi bi-question-circle-fill"></i> Help center</span>
                        <h1 id="faq-hero-heading">FAQ — how Ledrix stops dropped leads, mixed brands, and slow invoices</h1>
                        <p class="mkt-faq-hero-lead">
                            Straight answers for founders, agency owners, and closers evaluating Ledrix —
                            trials, multi-brand pipelines, seller panels, payments, and data privacy.
                        </p>
                        <div class="mkt-faq-hero-actions">
                            <a href="{{ route('index.get') }}#home-video" class="btn btn-lg mkt-btn-primary">Watch 60-sec demo</a>
                            <a href="#faq-topics" class="btn btn-lg mkt-btn-ghost">Browse topics</a>
                        </div>
                        <div class="mkt-faq-trust-row">
                            <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                            <span><i class="bi bi-buildings"></i> Multi-brand, one login</span>
                            <span><i class="bi bi-lightning-charge"></i> Full CRM in trial</span>
                            <span><i class="bi bi-headset"></i> Talk to sales anytime</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Quick topics --}}
        <section class="mkt-faq-topics" id="faq-topics" aria-labelledby="faq-topics-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="mkt-faq-topics-panel">
                            <div class="mkt-faq-topics-head text-center">
                                <h2 id="faq-topics-heading">Browse by topic</h2>
                                <p>Jump to the section that matches your question — getting started, product, billing, or support.</p>
                            </div>
                            <div class="row g-4">
                                @foreach ([
                                    ['icon' => 'bi-rocket-takeoff', 'title' => 'Getting started', 'desc' => 'Trials, signup & onboarding', 'href' => '#faq-getting-started'],
                                    ['icon' => 'bi-kanban', 'title' => 'Product & CRM', 'desc' => 'Features, brands & payments', 'href' => '#faq-product'],
                                    ['icon' => 'bi-receipt', 'title' => 'Pricing & billing', 'desc' => 'Plans, trials & subscriptions', 'href' => '#faq-billing'],
                                    ['icon' => 'bi-chat-left-text', 'title' => 'Sales & support', 'desc' => 'Demos & contact', 'href' => '#faq-support'],
                                ] as $topic)
                                    <div class="col-6 col-lg-3">
                                        <a href="{{ $topic['href'] }}" class="mkt-faq-topic-card">
                                            <span class="mkt-faq-topic-icon"><i class="bi {{ $topic['icon'] }}"></i></span>
                                            <strong>{{ $topic['title'] }}</strong>
                                            <span class="mkt-faq-topic-desc">{{ $topic['desc'] }}</span>
                                            <span class="mkt-faq-topic-link">View answers <i class="bi bi-arrow-right"></i></span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- General FAQs --}}
        @include('front.includes.faq-section', [
            'enterprise' => true,
            'sectionId' => 'faq-getting-started',
            'accordionId' => 'mktFaqGeneral',
            'title' => 'Getting started with Ledrix CRM',
            'lead' => 'What Ledrix is, who it\'s for, and how the free trial works.',
            'faqs' => array_slice(config('seo.faq', []), 0, 4),
        ])

        {{-- Mid-page sales CTA --}}
        <section class="mkt-faq-mid-cta" aria-labelledby="faq-mid-cta-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 w-100">
                        <div class="mkt-faq-mid-cta-card w-100">
                            <div class="row g-4 align-items-center w-100 mx-0">
                                <div class="col-lg-7">
                                    <span class="mkt-faq-mid-kicker"><i class="bi bi-graph-up-arrow"></i> Ready to evaluate?</span>
                                    <h2 id="faq-mid-cta-heading">Skip the spreadsheet chaos — see Ledrix live</h2>
                                    <p class="mkt-faq-mid-lead">
                                        @if ($minPrice !== null)
                                            Plans from ${{ number_format($minPrice, $minPrice == floor($minPrice) ? 0 : 2) }}/month.
                                        @endif
                                        Watch how lead routing and payment links work — then open a free workspace. No credit card.
                                    </p>
                                    <ul class="mkt-faq-mid-list">
                                        <li><i class="bi bi-check-circle-fill"></i> Real workspace, not a sandbox demo</li>
                                        <li><i class="bi bi-check-circle-fill"></i> Your brands stay private from day one</li>
                                        <li><i class="bi bi-check-circle-fill"></i> Upgrade or cancel anytime</li>
                                    </ul>
                                </div>
                                <div class="col-lg-5">
                                    <div class="mkt-faq-mid-box">
                                        <span class="mkt-faq-mid-box-badge"><i class="bi bi-stars"></i> Most popular path</span>
                                        <p class="mkt-faq-mid-box-title">{{ $popularPackage ? $trialStartCtaPopular : $trialStartCtaGeneric }}</p>
                                        <p class="mkt-faq-mid-box-desc">Set up your workspace in minutes. Compare plans anytime.</p>
                                        <div class="d-grid gap-2">
                                            @if ($popularPackage)
                                                <a href="{{ route('tenant.register.form', $popularPackage->slug) }}" class="btn btn-lg mkt-btn-primary">Start on {{ $popularPackage->name }}</a>
                                            @else
                                                <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">View plans &amp; start trial</a>
                                            @endif
                                            <a href="{{ route('contact-us.get') }}" class="btn btn-lg mkt-btn-ghost">Book a sales demo</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('front.includes.faq-section', [
            'enterprise' => true,
            'sectionId' => 'faq-product',
            'accordionId' => 'mktFaqProduct',
            'title' => 'Product, features & security',
            'lead' => 'How Ledrix handles brands, sellers, payments, portals, and data isolation.',
            'faqs' => array_slice(config('seo.faq', []), 4, 11),
        ])

        <p class="text-center mb-0 pb-4">
            <a href="{{ route('security.get') }}">Security &amp; compliance</a>
            <span class="text-secondary"> · </span>
            <a href="{{ route('features.get') }}#agency-branding">Custom domains</a>
            <span class="text-secondary"> · </span>
            <a href="{{ route('features.get') }}#integrations">API &amp; SSO</a>
        </p>

        @include('front.includes.faq-section', [
            'enterprise' => true,
            'sectionId' => 'faq-billing',
            'accordionId' => 'mktFaqBilling',
            'title' => 'Pricing, billing & subscriptions',
            'lead' => 'Trial mechanics, plan changes, payment methods, and what happens after trial.',
            'faqs' => config('seo.pricing_faq', []),
            'sectionAlt' => false,
        ])

        @include('front.includes.faq-section', [
            'enterprise' => true,
            'sectionId' => 'faq-support',
            'accordionId' => 'mktFaqSupport',
            'title' => 'Company & support',
            'lead' => 'Who built Ledrix and how to reach our sales team.',
            'faqs' => array_slice(config('seo.faq', []), 15, 2),
        ])

        {{-- Bottom CTA --}}
        <section class="mkt-cta-band text-center" aria-labelledby="faq-cta-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 w-100">
                        <h2 id="faq-cta-heading">Still losing deals to dropped leads?</h2>
                        <p class="mb-4 mkt-cta-lead">
                            Watch the demo, explore <a href="{{ route('features.get') }}" class="text-white fw-semibold">how Ledrix plugs each leak</a>,
                            or talk to sales — we respond within one business day.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="{{ route('index.get') }}#home-video" class="btn btn-lg btn-light fw-bold px-4">Watch the demo</a>
                            <a href="{{ route('contact-us.get') }}" class="btn btn-lg mkt-btn-ghost">Contact sales</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
