@extends('front.layout.layout')

@section('title', 'About Us')

@section('seo_title', 'About Ledrix CRM — Built to Stop Dropped Leads & Slow Closes')
@section('meta_description', 'Ledrix was built for founders and agency owners who lose deals to unclaimed leads, mixed brand pipelines, and slow payment links. Meet founder Zeeshan Asghar and see why teams switch.')
@section('meta_keywords', 'About Ledrix, Ledrix CRM company, Zeeshan Asghar, CRM for founders, agency CRM, stop dropped leads, seller panel CRM')

@section('og_type', 'profile')
@section('og_image_alt', 'Zeeshan Asghar — Founder & CEO of Ledrix CRM')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'About', 'url' => route('about.get')],
    ]])
    @php
        $orgUrl = rtrim((string) (config('seo.site_url') ?: config('app.url')), '/');
        $founderPhoto = config('seo.founder.photo');
        $founderImage = $founderPhoto ? asset($founderPhoto) : asset(config('seo.og_image'));
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'AboutPage',
        '@id' => route('about.get') . '#aboutpage',
        'name' => 'About Ledrix CRM',
        'description' => 'Company story and founder profile for Ledrix — sales CRM that stops dropped leads, mixed brands, and slow payment links.',
        'url' => route('about.get'),
        'isPartOf' => ['@id' => $orgUrl . '#website'],
        'about' => ['@id' => $orgUrl . '#organization'],
        'mainEntity' => [
            '@type' => 'Person',
            '@id' => route('about.get') . '#founder',
            'name' => config('seo.founder.name'),
            'jobTitle' => config('seo.founder.job_title'),
            'image' => $founderImage,
            'url' => config('seo.founder.linkedin'),
            'sameAs' => array_values(array_filter([
                config('seo.founder.linkedin'),
                config('seo.social.linkedin'),
            ])),
            'worksFor' => ['@id' => $orgUrl . '#organization'],
            'description' => config('seo.founder.description'),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'Organization',
        '@id' => $orgUrl . '#organization',
        'name' => config('seo.organization.name'),
        'legalName' => config('seo.organization.legal_name'),
        'url' => $orgUrl,
        'logo' => asset(config('seo.organization.logo')),
        'email' => config('seo.organization.email'),
        'foundingDate' => config('seo.organization.founding_date'),
        'founder' => ['@id' => route('about.get') . '#founder'],
        'sameAs' => config('seo.organization.same_as', []),
        'description' => 'Ledrix CRM — sales CRM helping founders and agencies stop dropped leads, keep brands separate, and collect payments faster.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('main-content')
    @php
        $packages = $packages ?? collect();
        $founderPhoto = config('seo.founder.photo');
    @endphp

    <div class="mkt-page mkt-page-about">
        {{-- Hero --}}
        <section class="mkt-about-hero text-center" aria-labelledby="about-hero-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 mkt-about-hero-inner">
                <span class="mkt-about-eyebrow"><i class="bi bi-building"></i> About Ledrix CRM</span>
                <h1 id="about-hero-heading">We built Ledrix because founders were tired of dropped leads and slow invoices</h1>
                <p class="mkt-about-hero-lead">
                    Spreadsheets, mixed brand pipelines, closers cherry-picking leads, and payment links that arrive after the buyer cools off —
                    Ledrix exists to kill those leaks for founders, agency owners, and sales teams.
                </p>
                <div class="mkt-hero-actions justify-content-center mb-4">
                    <a href="{{ route('index.get') }}#home-video" class="btn btn-lg mkt-btn-primary">Watch 60-sec demo</a>
                    @if ($popularPackage)
                        <a href="{{ route('tenant.register.form', $popularPackage->slug) }}" class="btn btn-lg mkt-btn-ghost">Open free workspace</a>
                    @else
                        <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-ghost">See plans — no card</a>
                    @endif
                </div>
                <div class="row g-3 justify-content-center mkt-about-hero-stats">
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="mkt-about-stat-pill">
                            <i class="bi bi-buildings"></i>
                            <span>Multi-brand, one login</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="mkt-about-stat-pill">
                            <i class="bi bi-person-badge"></i>
                            <span>Seller panels for closers</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="mkt-about-stat-pill">
                            <i class="bi bi-lightning-charge"></i>
                            <span>Payment links in seconds</span>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Who we serve — white break between blue sections --}}
        <section class="mkt-about-section mkt-section-white mkt-about-serve" aria-labelledby="about-serve-heading">
            <div class="container">
                <div class="text-center mb-4 mb-lg-5">
                    <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">Who we build for</span>
                    <h2 class="mkt-about-section-title" id="about-serve-heading">Built for people tired of dropped leads and dual CRM bills</h2>
                    <p class="mkt-about-lead mx-auto mb-0" style="max-width: 720px;">
                        Ledrix was shaped by agency pain: leads rotting in spreadsheets, brands split across tools, closers cherry-picking, and payment links that take forever after the Zoom yes.
                    </p>
                </div>
                <div class="row g-4">
                    <article class="col-md-4">
                        <div class="mkt-about-serve-card">
                            <div class="mkt-about-serve-icon"><i class="bi bi-rocket-takeoff"></i></div>
                            <h3 class="h5">Founders</h3>
                            <p class="mb-0 text-secondary">Get lead routing and payment links live without hiring ops first — one workspace, not a Frankenstein stack.</p>
                        </div>
                    </article>
                    <article class="col-md-4">
                        <div class="mkt-about-serve-card">
                            <div class="mkt-about-serve-icon"><i class="bi bi-buildings"></i></div>
                            <h3 class="h5">Agency owners</h3>
                            <p class="mb-0 text-secondary">Run multiple brands under one login — stop paying twice and mixing pipelines.</p>
                        </div>
                    </article>
                    <article class="col-md-4">
                        <div class="mkt-about-serve-card">
                            <div class="mkt-about-serve-icon"><i class="bi bi-people-fill"></i></div>
                            <h3 class="h5">Sales teams</h3>
                            <p class="mb-0 text-secondary">Closers see only their book; leadership sees every brand — no whole-DB browsing.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Mission --}}
        <section class="mkt-about-section mkt-about-section--muted" aria-labelledby="about-mission-heading">
            <div class="container">
                <div class="row align-items-start g-4 g-lg-5">
                    <div class="col-lg-5">
                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">Our mission</span>
                        <h2 class="mkt-about-section-title mb-0" id="about-mission-heading">CRM that follows the real revenue path</h2>
                    </div>
                    <div class="col-lg-7 mkt-about-prose">
                        <p class="mkt-about-lead">
                            Most CRMs become bloated databases sellers avoid. Ledrix was built around the leaks that cost agencies money — unclaimed leads, mixed brands, cherry-picking closers, and invoices that go out too late.
                        </p>
                        <div class="mkt-about-flow" role="list" aria-label="Ledrix revenue workflow">
                            <span role="listitem">Capture lead</span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            <span role="listitem">Assign seller</span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            <span role="listitem">Close order</span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            <span role="listitem">Get paid</span>
                        </div>
                        <p class="text-secondary mb-0">
                            Multiple brands under one login. Admins see everything; closers only see their book; clients get a portal. Explore <a href="{{ route('features.get') }}">how Ledrix plugs each leak</a> or <a href="{{ route('pricing.get') }}">compare plans</a>.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Principles --}}
        <section class="mkt-about-section mkt-about-principles-section" aria-labelledby="about-principles-heading">
            <div class="container">
                <div class="row align-items-end g-4 mb-5">
                    <div class="col-lg-7">
                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">What we stand for</span>
                        <h2 class="mkt-about-section-title mb-2" id="about-principles-heading">Three principles behind every Ledrix workspace</h2>
                        <p class="mkt-about-lead mb-0">
                            Every workspace is built to stop the same leaks: mixed brands, dropped leads, and closers who browse the whole book.
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <p class="text-secondary mb-0 mkt-about-principles-note">
                            <i class="bi bi-check2-circle text-primary"></i>
                            Built for agencies, closers, and multi-brand founders from day one.
                        </p>
                    </div>
                </div>

                <div class="mkt-about-principles-grid">
                    @foreach ([
                        [
                            'num' => '01',
                            'icon' => 'bi-layers',
                            'tone' => 'purple',
                            'title' => 'Your brands stay yours',
                            'text' => 'Run several brands under one login without mixing pipelines — or paying for a second CRM.',
                            'points' => ['Private company data', 'Multi-brand under one workspace'],
                        ],
                        [
                            'num' => '02',
                            'icon' => 'bi-signpost-split',
                            'tone' => 'indigo',
                            'title' => 'Lead to paid, no tool hop',
                            'text' => 'Capture, assign, close, send a payment link — one flow so the Zoom “yes” doesn’t die in Slack.',
                            'points' => ['Lead → order → payment', 'One source of truth'],
                        ],
                        [
                            'num' => '03',
                            'icon' => 'bi-person-workspace',
                            'tone' => 'green',
                            'title' => 'Closers can’t cherry-pick',
                            'text' => 'Seller panels show only their book — focused closing, not browsing everyone else’s deals.',
                            'points' => ['Assigned leads only', 'Less admin, more closing'],
                        ],
                    ] as $principle)
                        <article class="mkt-about-principle mkt-about-principle--{{ $principle['tone'] }}">
                            <div class="mkt-about-principle-top">
                                <span class="mkt-about-principle-num">{{ $principle['num'] }}</span>
                                <div class="mkt-about-principle-icon mkt-about-icon--{{ $principle['tone'] }}">
                                    <i class="bi {{ $principle['icon'] }}"></i>
                                </div>
                            </div>
                            <h3>{{ $principle['title'] }}</h3>
                            <p>{{ $principle['text'] }}</p>
                            <ul class="mkt-about-principle-points">
                                @foreach ($principle['points'] as $point)
                                    <li><i class="bi bi-check-lg"></i> {{ $point }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Sales growth band --}}
        <section class="mkt-section mkt-home-growth mkt-about-growth-band" aria-labelledby="about-growth-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="about-growth-heading">Why founders switch from generic CRMs to Ledrix</h2>
                <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                    Generic CRMs leave leads in piles, force dual-brand workarounds, and make payment a separate chore. Ledrix closes those gaps.
                </p>
                <div class="mkt-home-growth-grid mt-4 mt-lg-5">
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                        <h3>No more dropped leads</h3>
                        <p>Inbound hits the right closer — not a spreadsheet that goes cold overnight.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-lightning-fill" aria-hidden="true"></i>
                        <h3>Faster from yes to paid</h3>
                        <p>Pipeline, order, and payment link in one place — not a Slack chase after the Zoom.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                        <h3>Collect in the CRM</h3>
                        <p>Stripe and PayPal links from the same workspace — no tool-hopping when they’re ready to buy.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-bar-chart-fill" aria-hidden="true"></i>
                        <h3>Scale without chaos</h3>
                        <p>Add sellers and brands under one login — admins see everything; closers only their book.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Vision --}}
        <section class="mkt-about-section mkt-section-white" aria-labelledby="about-vision-heading">
            <div class="container">
                <div class="row mb-5">
                    <div class="col-lg-8 mx-auto text-center">
                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">Vision</span>
                        <h2 class="mkt-about-section-title" id="about-vision-heading">Sales ops that stop leaking as you grow</h2>
                        <p class="text-secondary mb-0">
                            Today: lead routing, seller panels, multi-brand workspaces, and payment links. Tomorrow: more automation on the same foundation — without another CRM migration.
                        </p>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <ul class="mkt-about-vision-list list-unstyled mb-0">
                            <li class="mkt-about-vision-item">
                                <div class="mkt-about-vision-icon"><i class="bi bi-cpu"></i></div>
                                <div>
                                    <h3>Smarter routing as you scale</h3>
                                    <p>Fewer leads left unclaimed — routing and performance signals built in, not bolted on later.</p>
                                </div>
                            </li>
                            <li class="mkt-about-vision-item">
                                <div class="mkt-about-vision-icon"><i class="bi bi-diagram-3"></i></div>
                                <div>
                                    <h3>Turn on what you need</h3>
                                    <p>Milestones, bonuses, PPC flows, projects, API — enable what stops your current leak; upgrade when you grow.</p>
                                </div>
                            </li>
                            <li class="mkt-about-vision-item">
                                <div class="mkt-about-vision-icon"><i class="bi bi-globe2"></i></div>
                                <div>
                                    <h3>Collect wherever clients pay</h3>
                                    <p>Stripe, PayPal, webhooks, custom domains, white-label — so payment doesn’t leave the CRM after the close.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Founder story --}}
        <section class="mkt-about-section mkt-about-story-section" id="founder" aria-labelledby="about-founder-heading">
            <div class="container">
                <div class="row align-items-center g-5 g-lg-6">
                    <div class="col-lg-6 order-lg-2">
                        <div class="mkt-founder-scene">
                            <div class="mkt-founder-scene-backdrop" aria-hidden="true"></div>
                            <figure class="mkt-founder-scene-photo">
                                @if ($founderPhoto)
                                    <img src="{{ asset($founderPhoto) }}"
                                        alt="{{ config('seo.founder.name') }} — Founder and CEO of Ledrix CRM"
                                        width="560" height="700" loading="lazy">
                                @else
                                    <div class="mkt-founder-photo-fallback" aria-hidden="true">
                                        <span>ZA</span>
                                    </div>
                                @endif
                            </figure>
                            <div class="mkt-founder-scene-accent" aria-hidden="true">
                                <span class="mkt-founder-scene-coffee"><i class="bi bi-cup-hot"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 order-lg-1">
                        <span class="mkt-about-eyebrow mkt-about-eyebrow--dark">Our story</span>
                        <h2 class="mkt-about-section-title" id="about-founder-heading">Founded to fix the chaos between leads, sellers, and payments</h2>

                        <blockquote class="mkt-about-founder-quote">
                            "Sellers didn't need more features — they needed one workspace that matched how deals actually move."
                        </blockquote>

                        <div class="mkt-about-story-prose">
                            <p>{{ config('seo.founder.story.origin') }}</p>
                            <p>{{ config('seo.founder.story.founding') }}</p>
                            <p class="mb-0">{{ config('seo.founder.story.today') }}</p>
                        </div>

                        <div class="mkt-about-founder-profile">
                            <h3 class="mkt-about-founder-name mb-1">{{ config('seo.founder.name') }}</h3>
                            <p class="mkt-about-founder-title">{{ config('seo.founder.job_title') }}, {{ config('seo.organization.name') }}</p>
                        </div>

                        <div class="mkt-about-founder-actions">
                            <a href="{{ config('seo.founder.linkedin') }}"
                                class="btn mkt-about-btn-primary"
                                target="_blank"
                                rel="noopener noreferrer me author">
                                <i class="bi bi-linkedin"></i> Connect on LinkedIn
                            </a>
                            <a href="{{ route('contact-us.get') }}" class="btn mkt-about-btn-outline">
                                <i class="bi bi-envelope"></i> Contact team
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="mkt-cta-band" aria-labelledby="about-cta-heading">
            <div class="container text-center">
                <h2 id="about-cta-heading">Ready to grow sales with Ledrix CRM?</h2>
                <p class="mb-4 mx-auto mkt-cta-lead" style="max-width: 600px;">
                    Tired of dropped leads and slow invoices? Watch the demo, then open a free workspace — no credit card required.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">View plans &amp; register</a>
                    <a href="{{ route('index.get') }}#home-video" class="btn btn-lg mkt-btn-ghost">Watch 60-sec demo</a>
                </div>
            </div>
        </section>
    </div>
@endsection
