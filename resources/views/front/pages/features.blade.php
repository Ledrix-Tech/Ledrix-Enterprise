@extends('front.layout.layout')

@section('title', 'Features')

@section('seo_title', 'Ledrix CRM Features — Stop Dropped Leads, Mix-Ups & Slow Invoices')
@section('meta_description', 'See how Ledrix stops spreadsheet lead piles, keeps agency brands separate under one login, gives closers a focused seller panel, and sends payment links in seconds after a yes.')
@section('meta_keywords', 'CRM features, stop dropped leads, multi-brand CRM, seller panel, payment links CRM, agency CRM features, sales pipeline CRM, Ledrix features')

@push('schema')
    @include('front.includes.schema-breadcrumbs', ['items' => [
        ['name' => 'Home', 'url' => route('index.get')],
        ['name' => 'Features', 'url' => route('features.get')],
    ]])
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Ledrix CRM Features for Sales Growth',
        'description' => 'Sales CRM capabilities that help teams capture leads, close deals, and scale revenue.',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Lead capture & multi-brand routing'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Seller & admin panels'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Orders, Stripe & PayPal payments'],
            ['@type' => 'ListItem', 'position' => 4, 'name' => 'Client portal & retention'],
            ['@type' => 'ListItem', 'position' => 5, 'name' => 'API, webhooks & integrations'],
            ['@type' => 'ListItem', 'position' => 6, 'name' => 'Reporting & performance dashboards'],
            ['@type' => 'ListItem', 'position' => 7, 'name' => 'Private company data — your brands stay yours'],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    <div class="mkt-page">
        {{-- Hero --}}
        <section class="mkt-hero text-center" aria-labelledby="features-hero-heading">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 mkt-hero-inner">
                <span class="mkt-hero-badge"><i class="bi bi-lightning-charge-fill"></i> Built to plug sales leaks</span>
                <h1 id="features-hero-heading">Stop dropped leads, mixed brands, and slow payment links</h1>
                <p class="mkt-hero-lead">
                    See exactly how Ledrix routes every lead to a closer, keeps agency brands separate under one login,
                    locks reps into a focused seller panel, and sends Stripe or PayPal links the moment a buyer says yes.
                </p>
                <div class="mkt-hero-actions">
                    <a href="{{ route('index.get') }}#home-video" class="btn btn-lg mkt-btn-primary">Watch 60-sec demo</a>
                    @if ($popularPackage)
                        <a href="{{ route('tenant.register.form', $popularPackage->slug) }}" class="btn btn-lg mkt-btn-ghost">Open free workspace</a>
                    @else
                        <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-ghost">See plans — no card</a>
                    @endif
                </div>
                <div class="mkt-trust-row">
                    <span><i class="bi bi-play-circle"></i> 60-sec demo</span>
                    <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                    <span><i class="bi bi-buildings"></i> Unlimited brands, one login</span>
                </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Without vs With --}}
        <section class="mkt-section mkt-section-white mkt-features-compare" aria-labelledby="features-compare-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="features-compare-heading">Why teams switch from spreadsheets to Ledrix</h2>
                <p class="mkt-section-lead mx-auto mb-4 mb-lg-5" style="max-width: 680px;">
                    If your current stack looks like the left column, you're leaving revenue on the table every week.
                </p>
                <div class="mkt-features-compare-grid text-start">
                    <div class="mkt-features-compare-col mkt-features-compare-col--without">
                        <h3><i class="bi bi-x-circle me-1"></i> Without Ledrix</h3>
                        <ul>
                            <li><i class="bi bi-x-lg"></i> Leads scattered across sheets, inboxes, and DMs</li>
                            <li><i class="bi bi-x-lg"></i> Sellers unsure who owns which follow-up</li>
                            <li><i class="bi bi-x-lg"></i> Payment links in one tool, CRM data in another</li>
                            <li><i class="bi bi-x-lg"></i> No client visibility — support eats seller time</li>
                            <li><i class="bi bi-x-lg"></i> Leadership flying blind on pipeline health</li>
                        </ul>
                    </div>
                    <div class="mkt-features-compare-col mkt-features-compare-col--with">
                        <h3><i class="bi bi-check-circle me-1"></i> With Ledrix CRM</h3>
                        <ul>
                            <li><i class="bi bi-check-lg"></i> Every lead captured, assigned, and tracked in one hub</li>
                            <li><i class="bi bi-check-lg"></i> Seller panels with clear ownership &amp; accountability</li>
                            <li><i class="bi bi-check-lg"></i> Orders &amp; payments tied directly to CRM records</li>
                            <li><i class="bi bi-check-lg"></i> Branded client portal for delivery &amp; retention</li>
                            <li><i class="bi bi-check-lg"></i> Real-time dashboards for admins and team leads</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Sales growth outcomes --}}
        <section class="mkt-section mkt-home-growth" aria-labelledby="features-growth-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="features-growth-heading">Four leaks Ledrix plugs for founders and sellers</h2>
                <p class="mkt-section-lead mx-auto" style="max-width: 760px;">
                    If leads sit unclaimed, brands get mixed, invoices go out late, or closers cherry-pick — you’re leaving money on the table every week.
                </p>
                <div class="mkt-home-growth-grid mt-4 mt-lg-5">
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                        <h3>Leads get an owner instantly</h3>
                        <p>Website and form leads route to a closer — not a shared spreadsheet waiting for someone to claim them.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-lightning-fill" aria-hidden="true"></i>
                        <h3>Closers stay in their lane</h3>
                        <p>Seller panels show only their book. No cherry-picking. No browsing the whole company database.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                        <h3>Payment links before they cool</h3>
                        <p>Client says yes on Zoom — your rep sends Stripe or PayPal from the lead card in seconds.</p>
                    </article>
                    <article class="mkt-home-growth-item">
                        <i class="bi bi-buildings" aria-hidden="true"></i>
                        <h3>Every brand under one login</h3>
                        <p>Stop paying for two CRMs. Keep design leads away from marketing leads — same account, clean pipelines.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Core capabilities --}}
        <section class="mkt-section mkt-section-alt" id="capabilities" aria-labelledby="features-core-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="features-core-heading">What Ledrix fixes — module by module</h2>
                <p class="mkt-section-lead">Each piece solves a real sales leak. Turn on what you need now. <a href="{{ route('pricing.get') }}">Compare plans</a>.</p>
                <div class="mkt-grid-3">
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-funnel"></i></div>
                        <h3 class="h5">Lead routing that claims ownership</h3>
                        <p>No more “who’s on this?” Every inbound lead gets a closer, a brand, and a trail — so nothing dies unclaimed.</p>
                        <span class="mkt-feature-outcome"><i class="bi bi-arrow-up-right"></i> Fewer dropped leads</span>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-people"></i></div>
                        <h3 class="h5">Seller panel for closers</h3>
                        <p>Reps only see what they need to close. Admins see everything. No distraction, no cherry-picking the best leads.</p>
                        <span class="mkt-feature-outcome"><i class="bi bi-arrow-up-right"></i> More dial time, less noise</span>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-credit-card"></i></div>
                        <h3 class="h5">Payment links from the deal</h3>
                        <p>Send Stripe or PayPal while the buyer is still hot — not hours later in a separate billing tool after Zoom ends.</p>
                        <span class="mkt-feature-outcome"><i class="bi bi-arrow-up-right"></i> Faster cash, fewer cold feet</span>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-person-badge"></i></div>
                        <h3 class="h5">Client portal</h3>
                        <p>Clients check invoices, project progress, and message the assigned seller — a human thread, not a bot. Less Slack. Better retention.</p>
                        <span class="mkt-feature-outcome"><i class="bi bi-arrow-up-right"></i> Less support load on closers</span>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-plug"></i></div>
                        <h3 class="h5">Website &amp; stack intake</h3>
                        <p>Pull leads from your site, forms, and tools automatically — so inbound doesn’t sit in someone’s inbox overnight.</p>
                        <span class="mkt-feature-outcome"><i class="bi bi-arrow-up-right"></i> Leads land while they’re hot</span>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-shield-check"></i></div>
                        <h3 class="h5">Your data stays yours</h3>
                        <p>Your brands, sellers, and clients stay private to your company. No mixing with another org on the platform.</p>
                        <span class="mkt-feature-outcome"><i class="bi bi-arrow-up-right"></i> Clean, private pipelines</span>
                    </article>
                </div>
            </div>
        </section>

        {{-- Mid-page CTA --}}
        <section class="mkt-features-mid-cta" aria-labelledby="features-mid-cta-heading">
            <div class="container">
                <div class="mkt-features-mid-cta-card">
                    <div class="row align-items-center g-4 g-lg-5">
                        <div class="col-lg-7">
                            <span class="mkt-features-mid-cta-kicker"><i class="bi bi-play-circle"></i> See the fix — then try it live</span>
                            <h2 id="features-mid-cta-heading">
                                Still routing leads through a spreadsheet?<br>
                                <span class="mkt-features-mid-cta-accent">Open a live Ledrix workspace and stop it today.</span>
                            </h2>
                            <p class="mkt-features-mid-cta-lead">
                                Watch the 60-second demo, then open a real workspace — no credit card. Route a lead, open a seller panel, send a payment link.
                            </p>
                            <ul class="mkt-features-mid-cta-list">
                                <li><i class="bi bi-check-circle-fill"></i> Real admin &amp; seller panels — not a fake sandbox</li>
                                <li><i class="bi bi-check-circle-fill"></i> Live lead routing to your closers</li>
                                <li><i class="bi bi-check-circle-fill"></i> Payment links from the lead card day one</li>
                                <li><i class="bi bi-check-circle-fill"></i> Multiple brands under one login</li>
                            </ul>
                        </div>
                        <div class="col-lg-5">
                            <div class="mkt-features-mid-cta-box">
                                <div class="mkt-features-mid-cta-box-badge">
                                    <i class="bi bi-unlock-fill"></i> No card required
                                </div>
                                <p class="mkt-features-mid-cta-box-title">Open a free workspace</p>
                                <p class="mkt-features-mid-cta-box-desc">Full CRM access. Cancel anytime.</p>
                                @if ($popularPackage)
                                    <a href="{{ route('tenant.register.form', $popularPackage->slug) }}" class="btn btn-lg mkt-btn-primary w-100 mb-2">
                                        Try {{ $popularPackage->name }} free
                                    </a>
                                @else
                                    <a href="{{ route('pricing.get') }}" class="btn btn-lg mkt-btn-primary w-100 mb-2">See plans — no card</a>
                                @endif
                                <a href="{{ route('index.get') }}#home-video" class="btn btn-lg mkt-btn-ghost w-100">
                                    <i class="bi bi-play-fill"></i> Watch 60-sec demo
                                </a>
                                <div class="mkt-features-mid-cta-trust">
                                    <span><i class="bi bi-credit-card-2-front"></i> No card for trial</span>
                                    <span><i class="bi bi-lightning-charge"></i> Live in minutes</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Deep dive --}}
        <section class="mkt-section mkt-features-deep" aria-labelledby="features-deep-heading">
            <div class="container">
                <div class="mkt-features-deep-header text-center">
                    <span class="mkt-features-deep-kicker">How Ledrix plugs each leak</span>
                    <h2 class="mkt-section-title" id="features-deep-heading">From inbound lead to paid — without tool-hopping</h2>
                    <p class="mkt-section-lead mx-auto">
                        Closers stop jumping HubSpot → Stripe → Slack. Founders stop guessing who owns which lead. Here's what that looks like in Ledrix.
                    </p>
                </div>

                <div class="mkt-features-deep-stack">
                    {{-- 01 Pipeline --}}
                    <article class="mkt-feature-panel">
                        <div class="row g-0 align-items-stretch">
                            <div class="col-lg-6 mkt-feature-panel__body">
                                <div class="mkt-feature-panel__inner">
                                    <div class="mkt-feature-panel__meta">
                                        <span class="mkt-feature-panel__num">01</span>
                                        <span class="mkt-feature-tag">Lead ownership</span>
                                    </div>
                                    <h3>Every lead gets a closer — instantly</h3>
                                    <p>Stop the shared spreadsheet pile. Website and form leads land on a specific seller and brand while they're still hot — with notes and history attached.</p>
                                    <ul class="mkt-feature-panel__list">
                                        <li><i class="bi bi-check2"></i> Brand-aware routing so pipelines don't mix</li>
                                        <li><i class="bi bi-check2"></i> Clear assignment — no "I thought you had it"</li>
                                        <li><i class="bi bi-check2"></i> Pull leads from your site and tools automatically</li>
                                        <li><i class="bi bi-check2"></i> Full activity trail on every opportunity</li>
                                    </ul>
                                    <div class="mkt-feature-panel__outcome">
                                        <i class="bi bi-graph-up-arrow"></i>
                                        <span><strong>Pain solved</strong> — Dropped / unclaimed leads</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mkt-feature-panel__media-col">
                                <div class="mkt-feature-panel__frame">
                                    <div class="mkt-feature-panel__frame-bar">
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--red"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--yellow"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--green"></span>
                                        <span class="mkt-feature-panel__frame-title">Ledrix — Lead pipeline</span>
                                    </div>
                                    <img src="{{ asset('front-assets/imgs/lead-m.jpg') }}" alt="Ledrix CRM lead management dashboard — assign sellers and track pipeline for sales growth" class="mkt-feature-panel__img" loading="lazy" width="600" height="400">
                                </div>
                            </div>
                        </div>
                    </article>

                    {{-- 02 Performance --}}
                    <article class="mkt-feature-panel mkt-feature-panel--reverse">
                        <div class="row g-0 align-items-stretch flex-lg-row-reverse">
                            <div class="col-lg-6 mkt-feature-panel__body">
                                <div class="mkt-feature-panel__inner">
                                    <div class="mkt-feature-panel__meta">
                                        <span class="mkt-feature-panel__num">02</span>
                                        <span class="mkt-feature-tag">Seller panel</span>
                                    </div>
                                    <h3>Closers only see what they need to close</h3>
                                    <p>Are reps distracted by the whole company database — or worse, cherry-picking the best leads? The seller panel is stripped down to their assignments and follow-ups.</p>
                                    <ul class="mkt-feature-panel__list">
                                        <li><i class="bi bi-check2"></i> Focused closer workspace — no admin clutter</li>
                                        <li><i class="bi bi-check2"></i> Ownership and follow-ups that don't live in someone's head</li>
                                        <li><i class="bi bi-check2"></i> Leaderboard &amp; performance visibility for managers</li>
                                        <li><i class="bi bi-check2"></i> Less busywork, more dial time</li>
                                    </ul>
                                    <div class="mkt-feature-panel__outcome">
                                        <i class="bi bi-graph-up-arrow"></i>
                                        <span><strong>Pain solved</strong> — Cherry-picking &amp; distraction</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mkt-feature-panel__media-col">
                                <div class="mkt-feature-panel__frame">
                                    <div class="mkt-feature-panel__frame-bar">
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--red"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--yellow"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--green"></span>
                                        <span class="mkt-feature-panel__frame-title">Ledrix — Seller workspace</span>
                                    </div>
                                    <img src="{{ asset('front-assets/imgs/automation.jpg') }}" alt="Ledrix seller workflow automation — leaderboard and performance tracking for sales teams" class="mkt-feature-panel__img" loading="lazy" width="600" height="400">
                                </div>
                            </div>
                        </div>
                    </article>

                    {{-- 03 Revenue --}}
                    <article class="mkt-feature-panel">
                        <div class="row g-0 align-items-stretch">
                            <div class="col-lg-6 mkt-feature-panel__body">
                                <div class="mkt-feature-panel__inner">
                                    <div class="mkt-feature-panel__meta">
                                        <span class="mkt-feature-panel__num">03</span>
                                        <span class="mkt-feature-tag">Payments</span>
                                    </div>
                                    <h3>Payment link in seconds after "yes"</h3>
                                    <p>How many deals die between Zoom "yes" and a manual invoice hours later? Generate Stripe or PayPal from the lead card before buyer's remorse sets in.</p>
                                    <ul class="mkt-feature-panel__list">
                                        <li><i class="bi bi-check2"></i> Stripe &amp; PayPal from inside the deal</li>
                                        <li><i class="bi bi-check2"></i> Milestone billing when projects need staged cash</li>
                                        <li><i class="bi bi-check2"></i> Order status from quote to paid</li>
                                        <li><i class="bi bi-check2"></i> No hopping to a separate billing tool mid-close</li>
                                    </ul>
                                    <div class="mkt-feature-panel__outcome">
                                        <i class="bi bi-graph-up-arrow"></i>
                                        <span><strong>Pain solved</strong> — Slow invoices &amp; cold buyers</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mkt-feature-panel__media-col">
                                <div class="mkt-feature-panel__frame">
                                    <div class="mkt-feature-panel__frame-bar">
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--red"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--yellow"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--green"></span>
                                        <span class="mkt-feature-panel__frame-title">Ledrix — Payments &amp; orders</span>
                                    </div>
                                    <img src="{{ asset('front-assets/imgs/chatt.jpg') }}" alt="Stripe and PayPal payment links in Ledrix CRM for faster revenue collection" class="mkt-feature-panel__img" loading="lazy" width="600" height="400">
                                </div>
                            </div>
                        </div>
                    </article>

                    {{-- 04 Integrations --}}
                    <article class="mkt-feature-panel mkt-feature-panel--reverse">
                        <div class="row g-0 align-items-stretch flex-lg-row-reverse">
                            <div class="col-lg-6 mkt-feature-panel__body">
                                <div class="mkt-feature-panel__inner">
                                    <div class="mkt-feature-panel__meta">
                                        <span class="mkt-feature-panel__num">04</span>
                                        <span class="mkt-feature-tag">Multi-brand</span>
                                    </div>
                                    <h3>Unlimited brands. One login. No mixed data.</h3>
                                    <p>Still paying for two CRM accounts to keep your web brand's leads away from your marketing brand's? Run every brand under one roof — pipelines stay clean.</p>
                                    <ul class="mkt-feature-panel__list">
                                        <li><i class="bi bi-check2"></i> Separate brand pipelines, shared reporting</li>
                                        <li><i class="bi bi-check2"></i> Connect your site and tools for auto intake</li>
                                        <li><i class="bi bi-check2"></i> Custom domain &amp; white-label on higher plans</li>
                                        <li><i class="bi bi-check2"></i> One bill instead of stacking CRM seats</li>
                                    </ul>
                                    <div class="mkt-feature-panel__outcome">
                                        <i class="bi bi-graph-up-arrow"></i>
                                        <span><strong>Pain solved</strong> — Dual CRM bills &amp; mixed brands</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mkt-feature-panel__media-col">
                                <div class="mkt-feature-panel__frame">
                                    <div class="mkt-feature-panel__frame-bar">
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--red"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--yellow"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--green"></span>
                                        <span class="mkt-feature-panel__frame-title">Ledrix — API &amp; integrations</span>
                                    </div>
                                    <img src="{{ asset('front-assets/imgs/integerate.jpg') }}" alt="Ledrix CRM API and webhook integrations for automated lead intake and sales workflows" class="mkt-feature-panel__img" loading="lazy" width="600" height="400">
                                </div>
                            </div>
                        </div>
                    </article>

                    {{-- 05 Insights --}}
                    <article class="mkt-feature-panel">
                        <div class="row g-0 align-items-stretch">
                            <div class="col-lg-6 mkt-feature-panel__body">
                                <div class="mkt-feature-panel__inner">
                                    <div class="mkt-feature-panel__meta">
                                        <span class="mkt-feature-panel__num">05</span>
                                        <span class="mkt-feature-tag">Visibility</span>
                                    </div>
                                    <h3>Founders see the real pipeline — not guesswork</h3>
                                    <p>Stop flying blind on who's closing, what's stuck, and which brand is leaking. Dashboards show leads, orders, and seller performance in one place.</p>
                                    <ul class="mkt-feature-panel__list">
                                        <li><i class="bi bi-check2"></i> Live view of leads, orders &amp; cash collected</li>
                                        <li><i class="bi bi-check2"></i> Seller performance without chasing Slack updates</li>
                                        <li><i class="bi bi-check2"></i> Client portal cuts "where's my project?" pings</li>
                                        <li><i class="bi bi-check2"></i> Decisions on data — not gut feel</li>
                                    </ul>
                                    <div class="mkt-feature-panel__outcome">
                                        <i class="bi bi-graph-up-arrow"></i>
                                        <span><strong>Pain solved</strong> — Blind pipeline &amp; tool fatigue</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mkt-feature-panel__media-col">
                                <div class="mkt-feature-panel__frame">
                                    <div class="mkt-feature-panel__frame-bar">
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--red"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--yellow"></span>
                                        <span class="mkt-feature-panel__frame-dot mkt-feature-panel__frame-dot--green"></span>
                                        <span class="mkt-feature-panel__frame-title">Ledrix — Analytics dashboard</span>
                                    </div>
                                    <img src="{{ asset('front-assets/imgs/report.jpg') }}" alt="Ledrix CRM admin reporting dashboard — seller performance and sales pipeline analytics" class="mkt-feature-panel__img" loading="lazy" width="600" height="400">
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Trial unlock --}}
        <section class="mkt-section mkt-features-trial-unlock" aria-labelledby="features-trial-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="features-trial-heading">Prove it in a live workspace — no credit card</h2>
                <p class="mkt-section-lead mx-auto mb-4 mb-lg-5" style="max-width: 720px;">
                    Watch the demo first if you want. Then open Ledrix and actually route a lead, open a seller panel, and send a payment link.
                </p>
                <div class="mkt-features-trial-grid">
                    <div class="mkt-features-trial-item">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        <span>Real admin &amp; seller panels</span>
                    </div>
                    <div class="mkt-features-trial-item">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span>Live lead routing</span>
                    </div>
                    <div class="mkt-features-trial-item">
                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                        <span>Payment links from the deal</span>
                    </div>
                    <div class="mkt-features-trial-item">
                        <i class="bi bi-buildings" aria-hidden="true"></i>
                        <span>Multiple brands, one login</span>
                    </div>
                    <div class="mkt-features-trial-item">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <span>Client portal</span>
                    </div>
                    <div class="mkt-features-trial-item">
                        <i class="bi bi-bar-chart" aria-hidden="true"></i>
                        <span>Pipeline visibility</span>
                    </div>
                </div>
                <div class="mt-4 mt-lg-5">
                    @if ($popularPackage)
                        <a href="{{ route('tenant.register.form', $popularPackage->slug) }}" class="btn btn-lg btn-light fw-bold px-4">Try {{ $popularPackage->name }} free</a>
                    @else
                        <a href="{{ route('pricing.get') }}" class="btn btn-lg btn-light fw-bold px-4">See plans — no card</a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Teams --}}
        <section class="mkt-section mkt-section-alt" aria-labelledby="features-roles-heading">
            <div class="container text-center">
                <h2 class="mkt-section-title" id="features-roles-heading">Built for founders, closers, and clients</h2>
                <p class="mkt-section-lead">Each role gets the view that stops their specific leak — not one cluttered screen for everyone.</p>
                <div class="mkt-grid-3 mt-4">
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-person-workspace"></i></div>
                        <h3 class="h5">Sellers &amp; closers</h3>
                        <p>Assigned leads, follow-ups, and payment links only. No admin noise. No cherry-picking other reps' books.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-shield-lock"></i></div>
                        <h3 class="h5">Founders &amp; admins</h3>
                        <p>Full control of brands, routing, users, orders, and cash — see who's closing and what's stuck.</p>
                    </article>
                    <article class="mkt-card text-start">
                        <div class="mkt-card-icon"><i class="bi bi-person-check"></i></div>
                        <h3 class="h5">Clients</h3>
                        <p>Own portal: invoices, project progress, briefs, tickets, and a message thread with their seller. Closers stop answering “where’s my project?” all day.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="mkt-cta-band text-center" aria-labelledby="features-cta-heading">
            <div class="container">
                <h2 id="features-cta-heading">Ready to stop dropping leads?</h2>
                <p class="mb-4 mx-auto" style="max-width: 560px;">
                    Watch the 60-second demo, then open a free workspace — no credit card. See multi-brand routing and seller panels live.
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('index.get') }}#home-video" class="btn btn-light btn-lg fw-bold px-4">Watch the demo</a>
                    <a href="{{ route('pricing.get') }}" class="btn btn-outline-light btn-lg px-4">Open free workspace</a>
                </div>
            </div>
        </section>

        @include('front.includes.faq-section', ['limit' => 4])
    </div>
@endsection
