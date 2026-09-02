@extends('front.layout.layout')

@section('title', 'Home')

@section('seo_title', 'Ledrix CRM — Stop Dropped Leads. Close Faster. Collect Payments.')
@section('meta_description', 'Ledrix CRM helps founders and agency owners stop dropping leads, keep brand pipelines separate, give closers a focused seller panel, and send payment links the moment a deal closes — without juggling HubSpot, Stripe, and spreadsheets.')
@section('meta_keywords', 'Ledrix CRM, sales CRM for agencies, CRM for founders, stop dropped leads, multi-brand CRM, seller panel CRM, payment links CRM, agency sales pipeline, closers CRM')

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
        'name' => 'Ledrix CRM — Stop Dropped Leads. Close Faster.',
        'description' => 'Sales CRM for founders and agencies: route leads to closers, keep brands separate, send payment links from the lead card, and stop deals falling between tools.',

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
            'description' => 'Sales CRM for founders and agency owners — stop dropped leads, keep brands separate, and collect payments without tool-hopping.',

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
                <span class="mkt-hero-badge"><i class="bi bi-lightning-charge-fill"></i> Built for founders who hate dropped leads</span>
                <h1 id="home-hero-heading">Your sales team is dropping leads. Ledrix makes sure that stops.</h1>
                <p class="mkt-hero-lead">
                    When a lead hits your site, it should land on a closer — not sit in a shared sheet waiting for someone to claim it.
                    Ledrix routes every lead, keeps your brands separate, gives reps a focused seller panel, and lets them send a payment link in seconds after “yes.”
                </p>
                <div class="mkt-hero-actions">
                    <a href="#home-video" class="btn btn-lg mkt-btn-primary">Watch 60-sec demo</a>
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-ghost">See plans — no card needed</a>
                </div>
                <div class="mkt-trust-row">
                    <span><i class="bi bi-play-circle"></i> 60-sec demo first</span>
                    <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                    <span><i class="bi bi-buildings"></i> Multiple brands, one login</span>
                    <span><i class="bi bi-person-badge"></i> Closers only see their pipeline</span>
                </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Founders, agencies & sales teams --}}
        <section class="mkt-section mkt-section-white mkt-home-audience" aria-labelledby="home-audience-heading">
            <div class="container">
                <div class="text-center mb-4 mb-lg-5">
                    <h2 class="mkt-section-title" id="home-audience-heading">Leads claimed. Brands unmixed. Payment links before the buyer goes cold.</h2>
                    <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                        Ledrix is built for founders, agency owners, and closers who lose money to dropped follow-ups, mixed pipelines, and slow invoices — not another contact spreadsheet.
                    </p>
                </div>
                <div class="row g-4">
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--founder">
                                <i class="bi bi-rocket-takeoff" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">For founders</h3>
                            <p class="text-secondary mb-4">
                                Stop losing deals in the gap between “yes” on a call and a manual invoice hours later. Your closers should send a payment link from the lead card in seconds.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Leads route to a closer — not a shared spreadsheet</li>
                                <li><i class="bi bi-check-circle-fill"></i> Seller panel: only what they need to close now</li>
                                <li><i class="bi bi-check-circle-fill"></i> Payment links from the deal — no tool-hopping</li>
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
                                Still paying for two CRM accounts just to keep your web brand’s leads away from your marketing brand’s? Run unlimited brands under one login — without mixing data.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Separate brand pipelines, one account</li>
                                <li><i class="bi bi-check-circle-fill"></i> Smart routing so the right closer gets the lead</li>
                                <li><i class="bi bi-check-circle-fill"></i> Admins see everything; reps see only their book</li>
                                <li><i class="bi bi-check-circle-fill"></i> Client updates without Slack archaeology</li>
                            </ul>
                        </div>
                    </article>
                    <article class="col-lg-4">
                        <div class="mkt-home-audience-card mkt-home-audience-card--teams h-100">
                            <div class="mkt-home-audience-icon mkt-home-audience-icon--teams">
                                <i class="bi bi-people-fill" aria-hidden="true"></i>
                            </div>
                            <h3 class="h4 mb-3">For sales teams</h3>
                            <p class="text-secondary mb-4">
                                Are closers cherry-picking leads or browsing the whole company database? Give them a stripped-down seller panel — they only see what they need to close right now.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Clear ownership — no more “who’s on this?”</li>
                                <li><i class="bi bi-check-circle-fill"></i> Follow-ups that don’t live in someone’s head</li>
                                <li><i class="bi bi-check-circle-fill"></i> Less admin busywork, more dial time</li>
                                <li><i class="bi bi-check-circle-fill"></i> Leadership sees the real pipeline — not guesswork</li>
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
                        Admin, seller, and client each sign into a different dashboard. Same company. Same brands. Nobody shares a login or a pile of tickets.
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
                                You see every brand, closer, lead, and order in your workspace. Routing, users, and cash — not a seller’s stripped-down book.
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
                                Closers only see assigned leads, orders, and the payment link on that deal. No cherry-picking. No browsing the whole database.
                            </p>
                            <ul class="mkt-check-list list-unstyled mb-0">
                                <li><i class="bi bi-check-circle-fill"></i> Their pipeline — not yours, not the company’s</li>
                                <li><i class="bi bi-check-circle-fill"></i> Message the client on the order, from the panel</li>
                                <li><i class="bi bi-check-circle-fill"></i> Mail when a lead is assigned to them</li>
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
                                Buyers log into a client portal — invoices, project progress, briefs, tickets, and a thread with their seller. They never see your other clients.
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
                <h2 class="mkt-section-title" id="home-growth-heading">What changes when your team runs on Ledrix</h2>
                <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                    Every inbound lead gets an owner. Every brand stays clean. Every closer stays focused. Every “yes” becomes a payment link in seconds.
                </p>
                <div class="mkt-home-growth-grid mt-4 mt-lg-5">
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <h3>Leads stop sitting idle</h3>
                        <p>Website leads route to a specific closer instantly — not a shared sheet waiting for someone to claim them.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-buildings" aria-hidden="true"></i>
                        <h3>Brands stay separate</h3>
                        <p>Run every agency brand under one login. No second CRM bill. No mixing design leads with marketing leads.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-cash-stack" aria-hidden="true"></i>
                        <h3>Payment links in 5 seconds</h3>
                        <p>Client says yes on Zoom — your rep sends Stripe or PayPal from the lead card before they get cold feet.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <h3>Closers stay focused</h3>
                        <p>Seller panels show only their assignments. No cherry-picking. No browsing the whole company database.</p>
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
                        <h2 class="mkt-section-title text-start" id="home-story-heading">Built because sales teams deserved better than spreadsheets</h2>
                        <div class="mkt-about-story-prose text-start">
                            @if (! empty($founderStory['origin']))
                                <p>{{ $founderStory['origin'] }}</p>
                            @endif
                            @if (! empty($founderStory['founding']))
                                <p>{{ $founderStory['founding'] }}</p>
                            @endif
                            <p class="mb-0">
                                Today, Ledrix is the practical sales operating system for teams that want predictable revenue growth — pipeline discipline, modern payments, and room to scale without enterprise bloat.
                                <a href="{{ route('about.get') }}">Read the full founder story</a> or explore <a href="{{ route('features.get') }}">CRM features</a>.
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="mkt-home-story-aside">
                            <div class="mkt-home-story-stat">
                                <span class="mkt-home-story-stat-num">1</span>
                                <span class="mkt-home-story-stat-label">workspace for leads, sellers, orders &amp; clients</span>
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
                <h2 class="mkt-section-title mb-2" id="home-video-heading">See the multi-brand routing — in 60 seconds</h2>
                <p class="text-muted mb-4 mx-auto" style="max-width: 680px;">
                    Watch how a lead hits the CRM, lands on the right closer, and how payment links go out from the same screen — before you create an account.
                </p>
                @if ($homeVideoExists && $homeVideoSrc)
                    <div class="mkt-video-block" data-mkt-video>
                        <div class="mkt-video-wrapper" id="mktVideoWrapper">
                            <img class="mkt-video-thumb"
                                src="{{ $homeVideoPoster ?? asset(config('seo.og_image')) }}"
                                alt="Ledrix CRM product demo — sales pipeline, seller panel, and payment links"
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
                        <p class="mb-0 small text-secondary">In the meantime, <a href="{{ route('pricing.get') }}">start your {{ $trialLabelGeneric }}</a> or <a href="{{ route('contact-us.get') }}">book a demo</a> with our team.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- How it works --}}
        <section class="mkt-section mkt-section-alt" aria-labelledby="home-how-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="home-how-heading">Four steps — from inbound to paid to visible</h2>
                <p class="mkt-section-lead">
                    No more HubSpot for tracking, Stripe for links, and Slack for updates. Those hops become one workspace — including the client seeing it land.
                </p>
                <div class="mkt-grid-4">
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">1</span>
                        <h3 class="h5">Lead lands on a closer</h3>
                        <p>Website, API, or form intake. The right brand and seller get it — nothing sits unworked in a shared pile.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">2</span>
                        <h3 class="h5">Closer works their panel</h3>
                        <p>Assignments, follow-ups, and notes in one place. They can’t cherry-pick or get lost in the company database.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">3</span>
                        <h3 class="h5">Payment link while they’re hot</h3>
                        <p>Stripe or PayPal from the lead card the moment they say yes — before buyer’s remorse sets in.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <span class="mkt-step-num">4</span>
                        <h3 class="h5">Client sees it land</h3>
                        <p>Portal invite and payment mail go out. They open invoices, project progress, and can message the closer — no “did it go through?” ping.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Pricing preview --}}
        @if ($packages->isNotEmpty())
        <section class="mkt-section mkt-home-pricing" aria-labelledby="home-pricing-heading">
            <div class="container">
                <div class="text-center mb-4 mb-lg-5">
                    <h2 class="mkt-section-title" id="home-pricing-heading">Pick a plan after you’ve seen the demo</h2>
                    <p class="mkt-section-lead mx-auto" style="max-width: 680px;">
                        @if ($minPrice !== null)
                            Plans from ${{ number_format($minPrice, $minPrice == floor($minPrice) ? 0 : 2) }}/month.
                        @endif
                        Watch the 60-second video first. Then open a live workspace — no credit card — and see if lead routing fits how your agency sells.
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
                                    See {{ $package->name }} live{{ (int) $package->trial_days > 0 ? ' — ' . (int) $package->trial_days . ' days free' : '' }}
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
                <h2 class="mkt-section-title" id="home-usecases-heading">If this is your week, Ledrix fixes it</h2>
                <p class="mkt-section-lead">Three leaks that kill agency revenue — and how Ledrix plugs each one.</p>
                <div class="mkt-grid-3">
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-funnel"></i></div>
                        <h3 class="h5">Leads die in the spreadsheet</h3>
                        <p>Inbound sits unclaimed while closers argue over who owns it. Ledrix assigns ownership the second the lead arrives.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-building"></i></div>
                        <h3 class="h5">Two brands, two CRM bills</h3>
                        <p>Agency owners launch sub-brands then pay twice — or mix pipelines. One login. Separate data. Unlimited brands.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-tools"></i></div>
                        <h3 class="h5">Tool fatigue kills closes</h3>
                        <p>Reps jump HubSpot → Stripe → Slack for every deal. Ledrix collapses tracking, payment links, and updates into one screen.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Testimonial --}}
        <section class="mkt-testimonial text-center" aria-labelledby="home-testimonial-heading">
            <div class="container">
                <h2 class="h4 fw-bold mb-4" id="home-testimonial-heading">Sales teams that switched to Ledrix</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-6">
                        <div class="mkt-quote-card h-100">
                            <blockquote class="mb-0">"We cut manual follow-ups in half and got every seller on the same pipeline within a week. Revenue visibility went from guesswork to clarity."</blockquote>
                            <footer class="small opacity-75 mt-3">— Agency operations lead</footer>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mkt-quote-card h-100">
                            <blockquote class="mb-0">"Our closers finally had one place for leads, orders, and payment links. We stopped losing deals between tools."</blockquote>
                            <footer class="small opacity-75 mt-3">— Sales team manager</footer>
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
                        <h2 class="mkt-section-title" id="why-ledrix-heading">Ledrix stops the leaks that cost you deals</h2>
                        <p class="mkt-section-lead">
                            Stop dropping leads. Stop mixing brand pipelines. Stop waiting hours for an invoice after a Zoom “yes.”
                            Stop letting closers cherry-pick while good leads go cold. One workspace — your data stays private, your brands stay separate, your reps stay focused.
                        </p>
                    </div>
                </div>
                <div class="row g-4 mt-2">
                    <div class="col-md-4">
                        <article class="mkt-card text-start h-100">
                            <h3 class="h5">Lead routing that claims ownership</h3>
                            <p class="mb-0 small text-secondary">Every inbound lead gets a closer. No shared pile. No “I thought you had it.” Built for teams that can’t afford ghosted follow-ups.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="mkt-card text-start h-100">
                            <h3 class="h5">Payments before the buyer cools</h3>
                            <p class="mb-0 small text-secondary">Generate Stripe or PayPal links inside the lead card in seconds — not hours later in a separate billing tool after the Zoom ends.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="mkt-card text-start h-100">
                            <h3 class="h5">Your data stays yours</h3>
                            <p class="mb-0 small text-secondary">Every company gets an isolated workspace. Brands, sellers, and clients never mix with another organization’s data on the platform.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        @include('front.includes.faq-section', ['limit' => 5])

        {{-- Trial CTA --}}
        <section class="mkt-cta-band" id="trial" aria-labelledby="home-cta-heading">
            <div class="container text-center">
                <h2 id="home-cta-heading">Still routing leads through a spreadsheet?</h2>
                <p class="mb-4 mx-auto" style="max-width: 600px;">
                    Watch the 60-second demo, then open a live workspace — no credit card. See multi-brand routing and seller panels with your own eyes.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="#home-video" class="btn btn-lg mkt-btn-primary">Watch the demo</a>
                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-ghost">Open a free workspace</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('front-assets/js/marketing.js') }}" defer></script>
@endpush
