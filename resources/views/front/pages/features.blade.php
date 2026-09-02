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
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Lead & pipeline'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Payments'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Multi-brand / multi-LLC'],
            ['@type' => 'ListItem', 'position' => 4, 'name' => 'Client portal'],
            ['@type' => 'ListItem', 'position' => 5, 'name' => 'Agency & branding'],
            ['@type' => 'ListItem', 'position' => 6, 'name' => 'Admin & oversight'],
            ['@type' => 'ListItem', 'position' => 7, 'name' => 'Integrations'],
            ['@type' => 'ListItem', 'position' => 8, 'name' => 'Security & compliance'],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/marketing.css') }}">
@endpush

@section('main-content')
    <div class="mkt-page mkt-page-features">
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

        {{-- Category jump --}}
        <nav class="mkt-section mkt-section-alt mkt-features-toc" id="capabilities" aria-label="Feature categories">
            <div class="container text-center">
                <p class="mkt-features-toc-kicker">Jump to a category</p>
                <div class="mkt-features-toc-list">
                    <a href="#lead-pipeline">Lead &amp; pipeline</a>
                    <a href="#payments">Payments</a>
                    <a href="#multi-brand">Multi-brand / multi-LLC</a>
                    <a href="#client-portal">Client portal</a>
                    <a href="#agency-branding">Agency &amp; branding</a>
                    <a href="#admin-oversight">Admin &amp; oversight</a>
                    <a href="#integrations">Integrations</a>
                    <a href="#security-compliance">Security</a>
                </div>
            </div>
        </nav>

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

        {{-- Lead & pipeline --}}
        <section class="mkt-feat-cat" id="lead-pipeline" aria-labelledby="cat-lead-heading">
            <div class="container">
                <div class="mkt-feat-cat__shell">
                    <div class="mkt-feat-cat__intro">
                        <span class="mkt-cat-kicker">Lead &amp; pipeline</span>
                        <h2 class="mkt-section-title" id="cat-lead-heading">Every lead gets a closer — not a shared pile</h2>
                        <p class="mkt-section-lead">Routing, ownership, and a seller panel that only shows their book.</p>
                    </div>
                    <div class="mkt-feat-list">
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Brand-aware routing</h3>
                                <p>Website and form leads land on a specific closer and brand while they’re still hot. Notes and history stay on the lead.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Seller panel</h3>
                                <p>Closers see assigned leads, follow-ups, and the payment link on that deal. They can’t cherry-pick someone else’s book.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Clear ownership</h3>
                                <p>No “I thought you had it.” Activity sits on the opportunity — not in someone’s head or a group chat.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-code-slash"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Site script &amp; API</h3>
                                <p>Drop a lead script on your site or POST to the lead API. The lead still lands on a closer and a brand — not an inbox.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-filter-circle"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Lead scoring</h3>
                                <p>On eligible plans, intake can score a lead as real vs junk before a closer wastes a dial. It is a classifier — not a bot that writes emails.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Renewals</h3>
                                <p>When a client is already on the books, open a renewal from the order instead of starting a new pile in a sheet.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        {{-- Payments --}}
        <section class="mkt-feat-cat mkt-feat-cat--cards" id="payments" aria-labelledby="cat-pay-heading">
            <div class="container">
                <div class="mkt-feat-cards-head">
                    <span class="mkt-cat-kicker">Payments</span>
                    <h2 class="mkt-section-title" id="cat-pay-heading">Payment link in seconds after “yes”</h2>
                    <p class="mkt-section-lead">Stripe or PayPal from the lead card — before the buyer goes cold.</p>
                </div>
                <div class="mkt-feat-cards">
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-credit-card"></i></span>
                        <h3>Links from the deal</h3>
                        <p>Generate Stripe or PayPal inside the CRM. No hop to a separate billing tab mid-close.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                        <h3>Order tracking</h3>
                        <p>Quote to paid stays on the order. Closers and clients can see whether it landed.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-layers"></i></span>
                        <h3>Milestone billing</h3>
                        <p>Staged cash when a project needs more than one invoice. On eligible plans.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                        <h3>Invoice from the order</h3>
                        <p>Generate the invoice on the deal you’re already in. On eligible plans.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-shield-exclamation"></i></span>
                        <h3>Disputes &amp; refunds</h3>
                        <p>Stripe and PayPal refund and dispute events can land back on the payment — so chargebacks aren’t a surprise Slack thread. On eligible plans.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-plugin"></i></span>
                        <h3>Payment webhooks</h3>
                        <p>Paid and failed updates hit the CRM from Stripe or PayPal. Closers don’t refresh another dashboard to ask “did it land?”</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Multi-brand — most detail --}}
        <section class="mkt-section mkt-section-white mkt-features-deep" id="multi-brand" aria-labelledby="cat-brand-heading">
            <div class="container">
                <div class="mkt-cat-head text-center">
                    <span class="mkt-cat-kicker">Multi-brand / multi-LLC</span>
                    <h2 class="mkt-section-title" id="cat-brand-heading">Unlimited brands. One login. No mixed data.</h2>
                    <p class="mkt-section-lead mx-auto">
                        This is the reason agencies stop stacking CRM seats. Every LLC or brand stays in its own pipeline. You still run one workspace.
                    </p>
                </div>
                <article class="mkt-feature-panel">
                    <div class="row g-0 align-items-stretch">
                        <div class="col-lg-6 mkt-feature-panel__body">
                            <div class="mkt-feature-panel__inner">
                                <h3>Stop paying for two CRMs to keep brands apart</h3>
                                <p>
                                    Web leads stay with the web brand. Marketing leads stay with marketing. Closers don’t trip over another LLC’s book.
                                    Admins see every brand in <em>this</em> company — not every tenant on Ledrix.
                                </p>
                                <ul class="mkt-feature-panel__list">
                                    <li><i class="bi bi-check2"></i> Separate brand pipelines under one account</li>
                                    <li><i class="bi bi-check2"></i> Routing that respects the brand the lead came from</li>
                                    <li><i class="bi bi-check2"></i> One bill instead of stacking seats per brand</li>
                                    <li><i class="bi bi-check2"></i> Sellers stay on their assignments — they don’t browse the whole shop</li>
                                    <li><i class="bi bi-check2"></i> Your workspace stays private from every other company on the platform</li>
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
                                    <span class="mkt-feature-panel__frame-title">Ledrix — Brands</span>
                                </div>
                                <img src="{{ asset('front-assets/imgs/integerate.jpg') }}" alt="Ledrix CRM multi-brand pipelines under one login" class="mkt-feature-panel__img" loading="lazy" width="600" height="400">
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        {{-- Client portal --}}
        <section class="mkt-feat-cat mkt-feat-cat--tint" id="client-portal" aria-labelledby="cat-client-heading">
            <div class="container">
                <div class="mkt-feat-cat__shell">
                    <div class="mkt-feat-cat__intro">
                        <span class="mkt-cat-kicker">Client portal</span>
                        <h2 class="mkt-section-title" id="cat-client-heading">Clients log in. They don’t email “where is it?”</h2>
                        <p class="mkt-section-lead">Their orders, invoices, tickets, and progress — not your pipeline.</p>
                    </div>
                    <div class="mkt-feat-list mkt-feat-list--stack">
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Orders &amp; invoices</h3>
                                <p>Clients see their own invoices and payment history. They don’t see other buyers.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-kanban"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Progress status</h3>
                                <p>When your team opens a project, they can check status and tasks in the portal. They can also message the assigned seller — a human thread, not a bot.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-life-preserver"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Tickets</h3>
                                <p>Clients open support tickets from the portal. Your closer, project manager, and admins get the mail — plus deadline reminders. Chat stays on the order; it doesn’t duplicate as email.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        {{-- Agency & branding --}}
        <section class="mkt-feat-cat" id="agency-branding" aria-labelledby="cat-agency-heading">
            <div class="container">
                <div class="mkt-feat-spotlight mkt-feat-spotlight--flip">
                    <div class="mkt-feat-spotlight__intro">
                        <span class="mkt-cat-kicker">Agency &amp; branding</span>
                        <h2 class="mkt-section-title" id="cat-agency-heading">Your brand on the door — when the plan includes it</h2>
                        <p class="mkt-section-lead">For agencies reselling to their own clients. Not a homepage pitch. One fact:</p>
                    </div>
                    <article class="mkt-feat-spotlight__body">
                        <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-globe2"></i></span>
                        <div class="mkt-feat-item__body">
                            <h3>Custom domain</h3>
                            <p>White-label the client portal under the agency’s own domain so buyers land on your URL, not Ledrix. Available on plans that include custom domains. You set the hostname and verify DNS in the product. Eligible plans can also drop Ledrix branding (logo) on the portal.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Admin & oversight --}}
        <section class="mkt-feat-cat mkt-feat-cat--tint mkt-feat-cat--cards" id="admin-oversight" aria-labelledby="cat-admin-heading">
            <div class="container">
                <div class="mkt-feat-cards-head">
                    <span class="mkt-cat-kicker">Admin &amp; oversight</span>
                    <h2 class="mkt-section-title" id="cat-admin-heading">You see the workspace. Closers see their book.</h2>
                    <p class="mkt-section-lead">Stats and controls stay here — not on the homepage. Room to grow without enterprise bloat.</p>
                </div>
                <div class="mkt-feat-cards">
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-bar-chart"></i></span>
                        <h3>Pipeline stats</h3>
                        <p>Leads, orders, cash collected, and closer performance for the brands in your workspace.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                        <h3>Controls</h3>
                        <p>Brands, users, routing, and payment accounts. Finance logins can be limited to payouts — they don’t run the full CRM.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-trophy"></i></span>
                        <h3>Seller visibility</h3>
                        <p>Leaderboard, performance views, and bonus tracking on eligible plans. Closers still don’t get the admin database.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                        <h3>Team seats</h3>
                        <p>Invite admins and finance from the workspace. Sellers and clients are created by your team — trial signup is the org owner.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                        <h3>2FA &amp; audit log</h3>
                        <p>Optional two-factor on admin and seller logins. Org owners get a workspace audit log. Details on the <a href="{{ route('security.get') }}">Security page</a>.</p>
                    </article>
                    <article class="mkt-feat-card">
                        <span class="mkt-feat-card__icon" aria-hidden="true"><i class="bi bi-credit-card-2-front"></i></span>
                        <h3>Your Ledrix bill</h3>
                        <p>Self-serve plan change and Stripe customer portal for the workspace subscription. That’s how you pay Ledrix — not how your buyers pay you.</p>
                    </article>
                </div>

                <div class="mkt-rbac-board">
                    <div class="mkt-rbac-board__head">
                        <span class="mkt-cat-kicker">Roles</span>
                        <h3>Who sees what</h3>
                    </div>
                    <div class="mkt-rbac-cols">
                        <article class="mkt-rbac-col mkt-rbac-col--admin">
                            <header class="mkt-rbac-col__head">
                                <span class="mkt-rbac-col__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                                <h4>Admin</h4>
                            </header>
                            <div class="mkt-rbac-col__block">
                                <p class="mkt-rbac-col__label mkt-rbac-col__label--yes">Sees</p>
                                <ul>
                                    <li>All brands, leads, sellers, orders, and reports in this workspace</li>
                                </ul>
                            </div>
                            <div class="mkt-rbac-col__block">
                                <p class="mkt-rbac-col__label mkt-rbac-col__label--no">Does not see</p>
                                <ul>
                                    <li>Other companies on Ledrix</li>
                                </ul>
                            </div>
                        </article>
                        <article class="mkt-rbac-col mkt-rbac-col--seller">
                            <header class="mkt-rbac-col__head">
                                <span class="mkt-rbac-col__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                                <h4>Seller</h4>
                            </header>
                            <div class="mkt-rbac-col__block">
                                <p class="mkt-rbac-col__label mkt-rbac-col__label--yes">Sees</p>
                                <ul>
                                    <li>Assigned leads, their orders, their clients, order chat, payment links on their deals</li>
                                </ul>
                            </div>
                            <div class="mkt-rbac-col__block">
                                <p class="mkt-rbac-col__label mkt-rbac-col__label--no">Does not see</p>
                                <ul>
                                    <li>Other closers’ books, admin settings, payout reports, other companies</li>
                                </ul>
                            </div>
                        </article>
                        <article class="mkt-rbac-col mkt-rbac-col--client">
                            <header class="mkt-rbac-col__head">
                                <span class="mkt-rbac-col__icon" aria-hidden="true"><i class="bi bi-person-check"></i></span>
                                <h4>Client</h4>
                            </header>
                            <div class="mkt-rbac-col__block">
                                <p class="mkt-rbac-col__label mkt-rbac-col__label--yes">Sees</p>
                                <ul>
                                    <li>Own orders, invoices, projects, briefs, tickets, messages with their seller</li>
                                </ul>
                            </div>
                            <div class="mkt-rbac-col__block">
                                <p class="mkt-rbac-col__label mkt-rbac-col__label--no">Does not see</p>
                                <ul>
                                    <li>Your pipeline, other clients, the seller panel, admin tools</li>
                                </ul>
                            </div>
                        </article>
                    </div>
                    <p class="mkt-rbac-board__note">Some teams also use a project-manager or finance login. Those stay narrower than admin — not a second full CRM.</p>
                </div>
            </div>
        </section>

        {{-- Integrations --}}
        <section class="mkt-feat-cat mkt-feat-cat--wide" id="integrations" aria-labelledby="cat-int-heading">
            <div class="container">
                <div class="mkt-feat-cat__shell">
                    <div class="mkt-feat-cat__intro">
                        <span class="mkt-cat-kicker">Integrations</span>
                        <h2 class="mkt-section-title" id="cat-int-heading">Your site and tools can push. Closers still own the lead.</h2>
                        <p class="mkt-section-lead">The pieces agencies check before they rule you out — not a homepage pitch.</p>
                    </div>
                    <div class="mkt-feat-list mkt-feat-list--stack">
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-window"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>Lead script</h3>
                                <p>Embed a brand script on your site. Form fields map into Ledrix. The lead still routes to a closer.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-key"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>API tokens</h3>
                                <p>Workspace tokens for the public lead API and <code>/api/v1</code> (company, usage, invoices, optional lead classify). On plans that include API access.</p>
                            </div>
                        </article>
                        <article class="mkt-feat-item">
                            <span class="mkt-feat-item__icon" aria-hidden="true"><i class="bi bi-broadcast"></i></span>
                            <div class="mkt-feat-item__body">
                                <h3>SSO &amp; SCIM</h3>
                                <p>OIDC sign-in and SCIM admin provisioning exist when we turn them on for you. Not a self-serve Okta panel in the trial. <a href="{{ route('security.get') }}">How that works</a>.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        {{-- Security link-out --}}
        <section class="mkt-feat-cat mkt-feat-cat--tint" id="security-compliance" aria-labelledby="cat-sec-heading">
            <div class="container">
                <a href="{{ route('security.get') }}" class="mkt-feat-security">
                    <div class="mkt-feat-security__copy">
                        <span class="mkt-cat-kicker">Security &amp; compliance</span>
                        <h2 class="mkt-section-title" id="cat-sec-heading">Isolation and GDPR live on their own page</h2>
                        <p class="mkt-section-lead">We don’t headline compliance here. If you need the facts, they’re written plainly next door.</p>
                    </div>
                    <span class="mkt-feat-security__cta">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        <span>
                            <strong>Security &amp; compliance</strong>
                            <em>Tenant isolation, GDPR requests, what we do not claim</em>
                        </span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </span>
                </a>
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
