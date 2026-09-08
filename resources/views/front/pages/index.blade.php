@extends('front.layout.layout')

@section('title', 'Home')

@section('seo_title', 'Ledrix CRM — Stop Mixing Client Brands, Leads, and Payments')
@section('meta_description', 'Agencies that run every client through one CRM and one Stripe account mix leads and land chargebacks on the wrong brand. Ledrix isolates each client’s pipeline and merchant. Free trial, no card.')
@section('meta_keywords', 'agency CRM, multi-brand CRM, client brand isolation, Stripe merchant routing, chargeback tracking, seller panel, client portal, digital marketing agency CRM, Ledrix CRM')

@push('head')
    @php
        $homeVideoHead = config('seo.home_video', []);
        $homeVideoHeadFile = $homeVideoHead['file'] ?? '';
    @endphp
    @if ($homeVideoHeadFile && file_exists(public_path($homeVideoHeadFile)))
        @php
            $homeVideoHeadUrl = asset($homeVideoHeadFile);
            $homeVideoHeadPoster = ! empty($homeVideoHead['poster']) && file_exists(public_path($homeVideoHead['poster']))
                ? asset($homeVideoHead['poster'])
                : asset(config('seo.og_image'));
        @endphp
        <meta property="og:video" content="{{ $homeVideoHeadUrl }}">
        <meta property="og:video:type" content="video/mp4">
        <meta property="og:video:width" content="1280">
        <meta property="og:video:height" content="720">
        <meta property="og:video:secure_url" content="{{ $homeVideoHeadUrl }}">
    @endif
@endpush

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
    ]])
    @php
        $packages = $packages ?? collect();
        $homeVideo = config('seo.home_video', []);
        $homeVideoFile = $homeVideo['file'] ?? '';
        $homeVideoExists = $homeVideoFile !== '' && file_exists(public_path($homeVideoFile));
        $homeVideoUrl = $homeVideoExists ? asset($homeVideoFile) : null;
        $homeVideoPosterPath = $homeVideo['poster'] ?? '';
        $homeVideoPoster = ($homeVideoPosterPath !== '' && file_exists(public_path($homeVideoPosterPath)))
            ? asset($homeVideoPosterPath)
            : asset(config('seo.og_image'));
        $orgUrl = rtrim((string) (config('seo.site_url') ?: config('app.url')), '/');
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'WebPage',
        '@id' => route('index.get') . '#webpage',
        'url' => route('index.get'),
        'name' => 'Ledrix CRM — Stop Mixing Client Brands, Leads, and Payments',
        'description' => 'Agency CRM that keeps each client brand’s leads, seller access, and Stripe or PayPal merchant payments apart — so a chargeback never lands on the wrong client.',

        'isPartOf' => ['@id' => $orgUrl . '#website'],
        'about' => ['@type' => 'SoftwareApplication', 'name' => 'Ledrix CRM'],
        'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => asset(config('seo.og_image'))],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @if ($homeVideoUrl)
        <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@'.'context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $homeVideo['title'] ?? 'Ledrix CRM product overview',
            'description' => $homeVideo['description'] ?? 'Ledrix CRM overview for sales teams.',
            'thumbnailUrl' => $homeVideoPoster,
            'duration' => $homeVideo['duration_iso'] ?? 'PT1M',
            'contentUrl' => $homeVideoUrl,
            'embedUrl' => $homeVideoUrl,
            'uploadDate' => '2026-08-01',
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('seo.organization.name', 'Ledrix'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset(config('seo.organization.logo', 'front-assets/imgs/logo-ic.png')),
                ],
            ],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
    @if ($packages->isNotEmpty())
        <script type="application/ld+json">
        {!! json_encode([
            '@'.'context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'Ledrix CRM',
            'description' => 'Agency CRM for teams running multiple client brands — isolated pipelines, merchant routing, chargeback tracking on the right client, seller panels, and a client portal.',

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
    @include('front.includes.schema-faq', ['faqs' => array_slice(config('seo.faq', []), 0, 5)])
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => 'How agencies keep client brands, leads, and payments apart in Ledrix',
        'description' => 'Four steps from inbound lead to the right merchant payment — without mixing client companies in one CRM.',
        'totalTime' => 'PT5M',
        'step' => [
            ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Lead lands on the right brand', 'text' => 'Website, API, or form intake routes the lead to that client brand and the assigned closer — not a shared pile.'],
            ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Closer works only their book', 'text' => 'The seller panel shows assigned records only. Commission-only closers cannot browse the agency’s full lead database.'],
            ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Payment hits the right merchant', 'text' => 'Stripe or PayPal generates under that client’s own merchant account, not a shared default.'],
            ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Client checks status themselves', 'text' => 'The buyer opens invoices and project progress in their portal — they do not see other clients, and they do not Slack the agency for an update.'],
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
        $founderStory = config('seo.founder.story', []);
        $homeVideo = config('seo.home_video', []);
        $homeVideoFile = $homeVideo['file'] ?? '';
        $homeVideoExists = $homeVideoFile !== '' && file_exists(public_path($homeVideoFile));
        $homeVideoSrc = $homeVideoExists ? asset($homeVideoFile) : null;
        $homeVideoPosterPath = $homeVideo['poster'] ?? '';
        $homeVideoPoster = ($homeVideoPosterPath !== '' && file_exists(public_path($homeVideoPosterPath)))
            ? asset($homeVideoPosterPath)
            : null;
        $minPrice = $packages->isNotEmpty() ? $packages->min('monthly_price') : null;
    @endphp
    <div class="mkt-page">
        {{-- Hero --}}
        <section class="mkt-hero text-center" aria-labelledby="home-hero-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 mkt-hero-inner">
                <span class="mkt-hero-badge"><i class="bi bi-lightning-charge-fill"></i> Built for agencies running more than one client brand</span>
                <h1 id="home-hero-heading">Stop running five clients through one CRM and one Stripe account.</h1>
                <p class="mkt-hero-lead">
                    When every brand dumps into the same pipeline, leads mix, a closer opens a book they should never see,
                    and a chargeback lands on the client who didn’t take the payment. Ledrix keeps each client’s leads,
                    access, and merchant money apart — under one agency workspace.
                </p>
                <div class="mkt-hero-actions">
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">{{ $trialStartCtaGeneric }}</a>
                    <a href="#home-video" class="btn btn-lg mkt-btn-ghost">Watch how brand isolation works</a>
                </div>
                <div class="mkt-trust-row">
                    <span><i class="bi bi-credit-card-2-front"></i> No card required</span>
                    <span><i class="bi bi-shop"></i> Each brand’s own Stripe / PayPal</span>
                    <span><i class="bi bi-person-badge"></i> Closers see assigned records only</span>
                    <span><i class="bi bi-shield-exclamation"></i> Chargebacks stay on the right client</span>
                </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Founders, agencies & sales teams --}}
        <section class="mkt-section mkt-section-white mkt-home-audience" aria-labelledby="home-audience-heading">
            <div class="container">
                <div class="text-center mb-4 mb-lg-5">
                    <h2 class="mkt-section-title" id="home-audience-heading">The mix-up is the product problem. Ledrix is the split.</h2>
                    <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                        Digital agencies in the US and UK still run client “companies” through one CRM, one payment login, and a spreadsheet on the side.
                        Ledrix is the sales CRM that treats each client brand as its own pipeline and merchant — without giving you a second SaaS bill per brand.
                    </p>
                </div>
                <div class="row g-4">
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card mkt-home-audience-card--teams h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--teams">
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">For closers</h3>
                            <p class="text-secondary mb-4">
                                Commission-only sellers should not see the whole agency database. Open a seller panel with assigned records only — then send that brand’s Stripe or PayPal link from the lead card.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Assigned book — not every client’s leads</li>
                                <li><i class="bi bi-check-circle-fill"></i> Payment link under that brand’s merchant</li>
                                <li><i class="bi bi-check-circle-fill"></i> Follow-ups on the deal, not in a shared sheet</li>
                                <li><i class="bi bi-check-circle-fill"></i> Reply on the order — no Slack dig</li>
                            </ul>
                        </div>
                    </article>
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--founder">
                                <i class="bi bi-rocket-takeoff" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">For founders</h3>
                            <p class="text-secondary mb-4">
                                One shared Stripe account is how a refund hits the wrong client. Give each brand its own merchant keys, then watch the real pipeline — not a spreadsheet that mixed three LLCs overnight.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Leads route to a brand and a closer — not a shared sheet</li>
                                <li><i class="bi bi-check-circle-fill"></i> Seller panel: only what they need to close now</li>
                                <li><i class="bi bi-check-circle-fill"></i> Payment links from the deal — correct merchant</li>
                                <li><i class="bi bi-check-circle-fill"></i> Live workspace in minutes — no card to start</li>
                            </ul>
                        </div>
                    </article>
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card mkt-home-audience-card--agency h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--agency">
                                <i class="bi bi-buildings" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">For agency owners</h3>
                            <p class="text-secondary mb-4">
                                Still stuffing every client “company” into one CRM so a closer can see Brand B’s leads while collecting on Brand A’s Stripe? Run unlimited brands under one login — without mixing data or merchants.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Separate brand pipelines, one agency workspace</li>
                                <li><i class="bi bi-check-circle-fill"></i> The right closer gets the right brand’s lead</li>
                                <li><i class="bi bi-check-circle-fill"></i> Admins see everything; reps see only their book</li>
                                <li><i class="bi bi-check-circle-fill"></i> Chargebacks tracked on that client’s payment</li>
                            </ul>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Role-scoped portals --}}
        <section class="mkt-section mkt-section-alt mkt-home-audience" aria-labelledby="home-roles-heading">
            <div class="container">
                <div class="text-center mb-4 mb-lg-5">
                    <h2 class="mkt-section-title" id="home-roles-heading">Every role gets its own view</h2>
                    <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                        Admin, seller, and client each sign into a different dashboard. Same agency. Same brands. A commission-only closer never sees the whole lead database — and a client never sees another brand’s invoice.
                    </p>
                </div>
                <div class="row g-4">
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--founder">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">Admin</h3>
                            <p class="text-secondary mb-4">
                                You see every client brand, closer, lead, and order in your workspace. Routing, merchant keys, and cash — not a seller’s stripped-down book.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Full CRM across all your brands</li>
                                <li><i class="bi bi-check-circle-fill"></i> Assign closers and watch the real pipeline</li>
                                <li><i class="bi bi-check-circle-fill"></i> Not mixed with any other company on Ledrix</li>
                            </ul>
                        </div>
                    </article>
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card mkt-home-audience-card--agency h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--agency">
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">Seller</h3>
                            <p class="text-secondary mb-4">
                                Closers open a focused seller panel: assigned leads, orders, and the payment link on that deal. No bloated CRM. No browsing another client’s book. No company-wide lead dump.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Their pipeline — not yours, not the company’s</li>
                                <li><i class="bi bi-check-circle-fill"></i> Send Stripe or PayPal without leaving the deal</li>
                                <li><i class="bi bi-check-circle-fill"></i> Mail when a lead is assigned — then they own it</li>
                            </ul>
                        </div>
                    </article>
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card mkt-home-audience-card--teams h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--teams">
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">Client</h3>
                            <p class="text-secondary mb-4">
                                Buyers log into a client portal — invoices, project progress, briefs, tickets, and a thread with their seller. They never see your other clients. They do not have to message you for a status update.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Their orders and invoices only</li>
                                <li><i class="bi bi-check-circle-fill"></i> Status and task progress on their projects</li>
                                <li><i class="bi bi-check-circle-fill"></i> No seller panel. No admin tools.</li>
                            </ul>
                        </div>
                    </article>
                </div>
                <p class="text-center text-secondary mt-4 mb-0 fw-semibold">
                    Three logins. One workspace. Separation without extra tools.
                </p>
            </div>
        </section>

        {{-- Order progress chat --}}
        <section class="mkt-section mkt-section-white" aria-labelledby="home-chat-heading">
            <div class="container">
                <div class="text-center mb-4 mb-lg-5">
                    <h2 class="mkt-section-title" id="home-chat-heading">They don’t have to ask — they can check</h2>
                    <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                        “Where’s my project?” should not live in email or a 40-person Slack. Clients and sellers talk on the order — a real message thread, not a bot.
                    </p>
                </div>
                <div class="row g-4 justify-content-center">
                    <article class="col-lg-6">
                        <div class="mkt-card text-start h-100">
                            <div class="mkt-card-icon"><i class="bi bi-chat-dots"></i></div>
                            <h3 class="h5">Client: check the order, then ping the closer</h3>
                            <p class="mb-3">Open the portal. See invoice status and project progress. If something’s stuck, message the assigned seller on that order — same thread they use.</p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Status without calling the office</li>
                                <li><i class="bi bi-check-circle-fill"></i> Human replies from your closer — not an AI</li>
                                <li><i class="bi bi-check-circle-fill"></i> Tied to that order, not a shared inbox</li>
                            </ul>
                        </div>
                    </article>
                    <article class="col-lg-6">
                        <div class="mkt-card text-start h-100">
                            <div class="mkt-card-icon"><i class="bi bi-headset"></i></div>
                            <h3 class="h5">Seller: check your book, then reply</h3>
                            <p class="mb-3">Closers open their seller panel and see their pipeline — assigned leads and orders only. If the client writes on that order, they reply there. No admin CRM. No Slack dig.</p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Their book — not the company database</li>
                                <li><i class="bi bi-check-circle-fill"></i> Same human thread the client already used</li>
                                <li><i class="bi bi-check-circle-fill"></i> Chat sits on the deal. It doesn’t replace routing.</li>
                            </ul>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Sales growth outcomes --}}
        <section class="mkt-section mkt-home-growth" aria-labelledby="home-growth-heading">
            <div class="container text-center">
                    <h2 class="mkt-section-title" id="home-growth-heading">Four leaks agencies feel every week</h2>
                    <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                    Mixed leads. Closers in the wrong book. Payments on the wrong merchant. Chargebacks with no client attached. Ledrix closes each one.
                    </p>
                    <div class="mkt-home-growth-grid mt-4 mt-lg-5">
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <h3>Leads stop mixing</h3>
                        <p>Website and form leads route to a named brand and closer — not a shared sheet where Client A’s inbound sits next to Client B’s.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <h3>Closers stay in their book</h3>
                        <p>Assigned leads, follow-ups, and the payment link. Commission-only sellers cannot open the agency’s full database.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-cash-stack" aria-hidden="true"></i>
                        <h3>Money hits the right merchant</h3>
                        <p>They say yes — you send Stripe or PayPal from the lead card under that client’s own account, not a shared default.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-buildings" aria-hidden="true"></i>
                        <h3>Chargebacks stay attached</h3>
                        <p>Refund and dispute events land on that client’s payment. You are not reconciling a surprise Slack thread against the wrong brand.</p>
                    </article>
                    </div>
            </div>
        </section>

        {{-- Short CRM story --}}
        <section class="mkt-section mkt-section-alt mkt-home-story" aria-labelledby="home-story-heading">
            <div class="container">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-lg-7">
                        <span class="mkt-home-story-kicker">The Ledrix story</span>
                        <h2 class="mkt-section-title text-start" id="home-story-heading">Built because agencies were tired of sharing a ledger</h2>
                        <div class="mkt-about-story-prose text-start">
                            @if (! empty($founderStory['origin']))
                                <p>{{ $founderStory['origin'] }}</p>
                            @endif
                            @if (! empty($founderStory['founding']))
                                <p>{{ $founderStory['founding'] }}</p>
                            @endif
                            <p class="mb-0">
                                Today, Ledrix is the sales CRM agencies use to keep client brands, leads, and payments apart — without a second SaaS stack per LLC.
                                <a href="{{ route('about.get') }}">Read the full founder story</a> or explore <a href="{{ route('features.get') }}">CRM features</a>.
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="mkt-home-story-aside">
                            <div class="mkt-home-story-stat">
                                <span class="mkt-home-story-stat-num">1</span>
                                <span class="mkt-home-story-stat-label">agency workspace for brands, leads, orders &amp; merchants</span>
                            </div>
                            <div class="mkt-home-story-stat">
                                <span class="mkt-home-story-stat-num">0</span>
                                <span class="mkt-home-story-stat-label">credit cards required to start your trial</span>
                            </div>
                            <div class="mkt-home-story-stat">
                                <span class="mkt-home-story-stat-num">∞</span>
                                <span class="mkt-home-story-stat-label">room to grow — add sellers &amp; brands as revenue scales</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 60-second video --}}
        <section class="mkt-video-section mkt-home-video" id="home-video" aria-labelledby="home-video-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title mb-2" id="home-video-heading">Watch a lead hit the right brand — in 60 seconds</h2>
                <p class="text-muted mb-4 mx-auto" style="max-width: 680px;">
                    See the lead land on a client brand, the closer open only their assigned book, and a payment link go out under that merchant — before you create an account.
                </p>
                @if ($homeVideoExists && $homeVideoSrc)
                    <div class="mkt-video-block" data-mkt-video>
                        <div class="mkt-video-wrapper" id="mktVideoWrapper">
                            <img class="mkt-video-thumb"
                                src="{{ $homeVideoPoster ?? asset(config('seo.og_image')) }}"
                                alt="Ledrix CRM demo for agencies — brand isolation, seller panel, and Stripe or PayPal under the right merchant"
                                width="960"
                                height="540"
                                loading="lazy">
                            <div class="mkt-play-btn" data-mkt-video-play role="button" tabindex="0"
                                aria-label="Play Ledrix CRM 60-second overview video">
                                <span><i class="bi bi-play-fill"></i></span>
                            </div>
                        </div>
                        <div class="mkt-video-modal" data-mkt-video-modal id="mktHomeVideoModal">
                            <button type="button" class="mkt-video-close" data-mkt-video-close aria-label="Close video">&times;</button>
                            <video data-mkt-video-player controls playsinline preload="metadata"
                                controlsList="nodownload noplaybackrate" disablePictureInPicture
                                oncontextmenu="return false;"
                                title="{{ $homeVideo['title'] ?? 'Ledrix CRM overview video' }}">
                                <source src="{{ $homeVideoSrc }}" type="video/mp4">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                    </div>
                    <details class="mkt-home-video-inline text-start">
                        <summary>Prefer to watch inline? Expand the player</summary>
                        <div class="mkt-home-video-frame">
                            <video class="mkt-home-video-player"
                                controls
                                playsinline
                                preload="metadata"
                                width="960"
                                height="540"
                                controlsList="nodownload noplaybackrate"
                                disablePictureInPicture
                                oncontextmenu="return false;"
                                poster="{{ $homeVideoPoster ?? '' }}"
                                title="{{ $homeVideo['title'] ?? 'Ledrix CRM overview video' }}">
                                <source src="{{ $homeVideoSrc }}" type="video/mp4">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                    </details>
                    <p class="small text-muted mt-3 mb-0">~60 seconds · No signup required · <a href="{{ route('features.get') }}">Explore all CRM features</a></p>
                @else
                    <div class="alert alert-light border mx-auto" style="max-width: 560px;" role="status">
                        <p class="mb-2 fw-semibold">Video preview loading soon</p>
                        <p class="mb-0 small text-secondary">In the meantime, <a href="{{ route('pricing.get') }}">{{ $trialStartCtaGeneric }}</a> or <a href="{{ route('contact-us.get') }}">book a demo</a> with our team.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- How it works --}}
        <section class="mkt-section mkt-section-alt" aria-labelledby="home-how-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="home-how-heading">Four steps — from inbound lead to the right merchant</h2>
                <p class="mkt-section-lead">
                    No more one CRM dump, one Stripe login, and Slack for “which client was that chargeback?” Closers work one screen. Money stays on the brand that earned it.
                </p>
                <div class="mkt-grid-4">
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">1</span>
                        <h3 class="h5">Lead lands on the right brand</h3>
                        <p>Website, API, or form. That client’s closer gets it instantly — nothing sits unworked in a shared pile with every other LLC.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">2</span>
                        <h3 class="h5">Closer works only their book</h3>
                        <p>Assignments, follow-ups, and notes in one seller panel. They cannot browse the agency database — and they cannot collect on the wrong brand.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">3</span>
                        <h3 class="h5">Payment hits the right merchant</h3>
                        <p>Stripe or PayPal from the lead card — generated under that client’s own account, not a shared default.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">4</span>
                        <h3 class="h5">Client checks it themselves</h3>
                        <p>Portal invite and payment mail go out. They open invoices and project progress — no “where’s my project?” ping, and no view into your other clients.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Pricing preview --}}
        @if ($packages->isNotEmpty())
        <section class="mkt-section mkt-home-pricing" aria-labelledby="home-pricing-heading">
            <div class="container">
                <div class="text-center mb-4 mb-lg-5">
                    <h2 class="mkt-section-title" id="home-pricing-heading">Try the workspace first. Pick a plan after.</h2>
                    <p class="mkt-section-lead mx-auto" style="max-width: 680px;">
                        @if ($minPrice !== null)
                            Plans from ${{ number_format($minPrice, $minPrice == floor($minPrice) ? 0 : 2) }}/month.
                        @endif
                        Open a live agency workspace — no credit card — and send a payment link under the right merchant. Watch the 60-second demo if you want the tour first.
                        <a href="{{ route('pricing.get') }}">Compare all plans</a>.
                    </p>
                </div>
                <div class="row g-4 justify-content-center">
                    @foreach ($packages->take(3) as $package)
                        <div class="col-md-6 col-lg-4">
                            <article class="mkt-home-pricing-card {{ $package->is_popular ? 'is-popular' : '' }}">
                                @if ($package->badge_text)
                                    <span class="mkt-home-pricing-badge">{{ $package->badge_text }}</span>
                                @elseif ($package->is_popular)
                                    <span class="mkt-home-pricing-badge">Most popular</span>
                                @endif
                                <h3 class="mkt-home-pricing-name">{{ $package->name }}</h3>
                                @if ($package->description)
                                    <p class="mkt-home-pricing-desc">{{ $package->description }}</p>
                                @endif
                                <div class="mkt-home-pricing-price">
                                    <span class="amount">${{ number_format($package->monthly_price, $package->monthly_price == floor($package->monthly_price) ? 0 : 2) }}</span>
                                    <span class="period">/month</span>
                                </div>
                                @if ((int) $package->trial_days > 0)
                                    <p class="mkt-home-pricing-trial">
                                        <i class="bi bi-gift"></i> {{ (int) $package->trial_days }}-day free trial
                                    </p>
                                @endif
                                <a href="{{ route('tenant.register.form', $package->slug) }}" class="btn mkt-btn-primary w-100">
                                    @if ((int) $package->trial_days > 0)
                                        Start {{ (int) $package->trial_days }}-day free trial — no card
                                    @else
                                        Get started on {{ $package->name }}
                                    @endif
                                </a>
                            </article>
                        </div>
                    @endforeach
                </div>
                @if ($packages->count() > 3)
                    <p class="text-center mt-4 mb-0">
                        <a href="{{ route('pricing.get') }}" class="fw-semibold">See all {{ $packages->count() }} plans &rarr;</a>
                    </p>
                @endif
            </div>
        </section>
        @endif

        {{-- Use cases --}}
        <section class="mkt-section mkt-section-muted" aria-labelledby="home-usecases-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="home-usecases-heading">If this is your week, the mix-up is already costing you</h2>
                <p class="mkt-section-lead">Three failures generic CRMs ignore — and how Ledrix stops each one.</p>
                <div class="mkt-grid-3">
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-funnel"></i></div>
                        <h3 class="h5">Leads from three clients in one sheet</h3>
                        <p>Inbound sits unclaimed, then a closer works the wrong brand. Ledrix assigns the lead to that client’s closer the second it arrives.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-credit-card"></i></div>
                        <h3 class="h5">Chargeback on the wrong Stripe account</h3>
                        <p>One merchant for the whole agency is how Client B pays for Client A’s dispute. Ledrix generates the link under that brand’s own Stripe or PayPal.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-eye-slash"></i></div>
                        <h3 class="h5">A closer saw a book they shouldn’t</h3>
                        <p>Commission-only sellers should not browse the agency database. The seller panel shows assigned records only.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Testimonial --}}
        <section class="mkt-testimonial text-center" aria-labelledby="home-testimonial-heading">
            <div class="container">
                <h2 class="h4 fw-bold mb-4" id="home-testimonial-heading">What agencies say after they split the ledger</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-6">
                        <div class="mkt-quote-card h-100">
                            <blockquote class="mb-0">"A chargeback used to mean a Slack hunt for which client it belonged to. Now it sits on that brand’s payment. We stopped guessing."</blockquote>
                            <footer class="small opacity-75 mt-3">— Agency owner, multi-brand growth shop</footer>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mkt-quote-card h-100">
                            <blockquote class="mb-0">"My panel is just my book. I cannot see the other clients. Payment links go out under the brand I’m closing — not a shared Stripe dump."</blockquote>
                            <footer class="small opacity-75 mt-3">— Closer, multi-brand team</footer>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SEO: product summary --}}
        <section class="mkt-section" aria-labelledby="why-ledrix-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 text-center">
                        <h2 class="mkt-section-title" id="why-ledrix-heading">Ledrix CRM: the sales CRM that won’t mix your clients</h2>
                        <p class="mkt-section-lead">
                            Stop dumping every brand into one pipeline. Stop collecting on one Stripe account. Stop letting closers see data they shouldn’t.
                            One agency workspace — isolated brands, the right merchant, and a client portal so buyers stop asking for updates.
                        </p>
                    </div>
                </div>
                <div class="row g-4 mt-2">
                    <div class="col-md-4">
                        <article class="mkt-card text-start h-100">
                            <h3 class="h5">Brand routing that claims ownership</h3>
                            <p class="mb-0 small text-secondary">Every inbound lead hits a client brand and a closer. No shared pile. No “I thought that was Brand B’s.” Built for agencies that cannot afford mixed lists.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="mkt-card text-start h-100">
                            <h3 class="h5">Payments on the right merchant</h3>
                            <p class="mb-0 small text-secondary">Generate Stripe or PayPal inside the lead card under that client’s own keys — not hours later in a shared billing login after the Zoom ends.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="mkt-card text-start h-100">
                            <h3 class="h5">Your data stays yours</h3>
                            <p class="mb-0 small text-secondary">Every agency gets an isolated workspace. Brands, sellers, and clients never mix with another organization’s data — and closers never mix with another client’s book.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        @include('front.includes.faq-section', [
            'limit' => 5,
            'title' => 'Agencies ask this before they try Ledrix',
            'lead' => 'Brand isolation, merchant routing, seller access, and whether you need a card to start.',
        ])

        {{-- Trial CTA --}}
        <section class="mkt-cta-band" id="trial" aria-labelledby="home-cta-heading">
            <div class="container text-center">
                <h2 id="home-cta-heading">Your next chargeback should already know which client it belongs to.</h2>
                <p class="mb-4 mx-auto" style="max-width: 600px;">
                    Open a live agency workspace — no credit card. Route a brand’s lead, send Stripe or PayPal under that merchant, and keep closers out of books they shouldn’t see.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary">{{ $trialStartCtaGeneric }}</a>
                    <a href="#home-video" class="btn btn-lg mkt-btn-ghost">Watch the 60-sec demo</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front-assets/js/marketing.js') }}" defer></script>
@endpush
