<?php

return [

    'site_name' => 'Ledrix CRM',

    'default_title' => 'Ledrix CRM — Agency CRM That Keeps Client Brands, Leads, and Payments Apart',

    'default_description' => 'Stop running every client brand through one CRM and one payment account. Ledrix isolates each brand’s leads, routes Stripe and PayPal to the right merchant, and tracks chargebacks on the client who actually took the money. Free trial, no card.',

    'default_keywords' => 'agency CRM, multi-brand CRM, client brand isolation, Stripe merchant routing, PayPal payment links, chargeback tracking CRM, seller panel, client portal, digital marketing agency CRM, Ledrix CRM',

    'twitter_handle' => '@ledrixcrm',

    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),

    'facebook_domain_verification' => env('FACEBOOK_DOMAIN_VERIFICATION'),

    /** Public marketing site URL (use https://ledrix.co in production — do not leave APP_URL as localhost). */
    'site_url' => env('SEO_SITE_URL', env('APP_URL')),

    'theme_color' => '#4338ca',

    'og_image' => 'front-assets/imgs/logo-ic.png',

    'front_logo' => 'front-assets/imgs/logo-ic.png',
    'front_favicon' => 'front-assets/imgs/fv-icon.png',
    'front_favicon_32' => 'front-assets/imgs/favicon-32.png',
    'front_apple_touch_icon' => 'front-assets/imgs/apple-touch-icon.png',

    'launch_video' => [
        'file' => 'front-assets/media/ledrix-crm-audit-v1-web.mp4',
        'poster' => 'front-assets/media/ledrix-crm-audit-v1-thumb.jpg',
        'captions' => 'front-assets/media/ledrix-crm-audit-v1.vtt',
        'download_name' => 'Ledrix-CRM-SaaS-V1-Audit.mp4',
        'download_full' => 'front-assets/media/ledrix-crm-audit-v1-full.mp4',
        'title' => 'Ledrix CRM V1 — Admin & Seller walkthrough',
    ],

    /** 60-second homepage product overview (public/front-assets/media/front-video.mp4). */
    'home_video' => [
        'file'         => 'front-assets/media/front-video.mp4',
        'poster'       => 'front-assets/media/ledrix-thumb.PNG',
        'title'        => 'Ledrix CRM — 60-second overview for agencies running multiple client brands',
        'description'  => 'Watch a lead land on the right brand, a closer work only their assigned book, and a Stripe or PayPal link generate under that client’s merchant — before you sign up.',
        'duration_iso' => 'PT1M',
    ],

    'social' => [
        'facebook'  => env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/profile.php?id=100063861860966'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL', 'https://www.instagram.com/ledrixtech/'),
        'linkedin'  => env('SOCIAL_LINKEDIN_URL', 'https://www.linkedin.com/company/ledrix-technologies'),
    ],

    'organization' => [
        'name' => 'Ledrix',
        'legal_name' => 'Ledrix CRM',
        'url' => null,
        'logo' => 'front-assets/imgs/logo-ic.png',
        'email' => 'hello@ledrix.co',
        'founding_date' => '2024',
        'same_as' => array_values(array_filter([
            env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/ledrixcrm'),
            env('SOCIAL_INSTAGRAM_URL', 'https://www.instagram.com/ledrixcrm'),
            env('SOCIAL_LINKEDIN_URL', 'https://www.linkedin.com/in/zeeshan-asghar-500a40255/'),
        ])),
    ],

    'founder' => [
        'name' => 'Zeeshan Asghar',
        'job_title' => 'Founder & CEO',
        'linkedin' => 'https://www.linkedin.com/in/zeeshan-asghar-500a40255/',
        'photo' => 'front-assets/imgs/founder-lounge.png',
        'story' => [
            'origin' => 'While working with digital agencies, Zeeshan Asghar kept seeing the same failure: five client “companies” stuffed into one CRM and one Stripe login. Leads mixed. Closers opened records they should never see. Refunds and chargebacks landed on the wrong client. Spreadsheets and generic CRMs made it worse, not better.',
            'founding' => 'In 2024 he founded Ledrix so an agency could run multiple client brands in one workspace without sharing a ledger — each brand’s leads, access, and merchant payments stay apart.',
            'today' => 'Ledrix is the agency CRM built for that split: isolated brand pipelines, Stripe and PayPal links under the right merchant, chargeback tracking on the payment that was actually disputed, plus seller and client portals so nobody hunts Slack for status. Led by Zeeshan, the team keeps shipping the operating system growing agencies actually run — not another bloated contact database.',
        ],
        'description' => 'Zeeshan Asghar founded Ledrix to stop agencies from mixing client leads and payments in one CRM — with brand isolation, merchant routing, and role-based seller and client portals.',
    ],

    'sitemap' => [
        ['path' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['path' => '/features', 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['path' => '/pricing', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['path' => '/about', 'priority' => '0.85', 'changefreq' => 'monthly'],
        ['path' => '/faq', 'priority' => '0.85', 'changefreq' => 'monthly'],
        ['path' => '/contact-us', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['path' => '/terms', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ['path' => '/privacy-policy', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ['path' => '/security', 'priority' => '0.5', 'changefreq' => 'yearly'],
        ['path' => '/status', 'priority' => '0.6', 'changefreq' => 'daily'],
    ],

    'robots_disallow' => [
        '/admin',
        '/seller',
        '/super-admin',
        '/client',
        '/compliance',
        '/upwork',
        '/sign-in',
        '/register',
        '/tenant-profile',
        '/verify-email',
        '/billing',
        '/pay/',
        '/api/',
        '/lp',
    ],

    'faq' => [
        [
            'question' => 'What is Ledrix CRM?',
            'answer' => 'Ledrix is a multi-tenant CRM for agencies that run multiple client brands. It stops you stuffing every company into one pipeline and one payment account — leads stay on the right brand, Stripe and PayPal links generate under that client’s merchant, and chargebacks attach to the payment that was actually disputed.',
        ],
        [
            'question' => 'What does a sales closer get in Ledrix?',
            'answer' => 'A seller panel with assigned leads, follow-ups, orders, and the payment link on that deal. Commission-only closers cannot browse the agency’s full lead database or another client’s book. They get mail when a lead is assigned, and they reply to that client on the order — not in Slack.',
        ],
        [
            'question' => 'Can I send a payment link while I am still on the call?',
            'answer' => 'Yes. Generate Stripe or PayPal from the lead card in seconds after the buyer says yes — under that client brand’s own merchant account, not a shared default. You do not hop to a separate billing tab mid-close.',
        ],
        [
            'question' => 'Who is Ledrix CRM for?',
            'answer' => 'US and UK digital marketing and growth agencies, plus sales teams that manage more than one client brand — people currently juggling spreadsheets, a generic CRM, or a single Stripe login that mixes every client’s money.',
        ],
        [
            'question' => 'Is Ledrix CRM free to try?',
            'answer' => 'Yes. Start a plan-based free trial with a real agency workspace — no credit card required. You only pay if you continue after the trial.',
        ],
        [
            'question' => 'How is Ledrix different from other CRM software?',
            'answer' => 'Generic CRMs treat your agency like one company. Ledrix treats each client brand as its own pipeline and merchant. Closers see only assigned records. Refunds and chargebacks land on the client who took the payment — not on whoever happened to be the default Stripe account.',
        ],
        [
            'question' => 'Does Ledrix support multiple brands or teams?',
            'answer' => 'Yes. Run every client brand under one agency workspace without mixing leads or payments. Admins see the full picture; sellers only see their assignments; each client gets a portal for their own orders and invoices.',
        ],
        [
            'question' => 'Do clients get their own portal?',
            'answer' => 'Yes. Each client signs into a client dashboard — not the seller panel. They see their own orders, invoices and payment history, project progress when your team opens a project, briefs, tickets, and a message thread with the assigned seller. They cannot see other clients or your pipeline.',
        ],
        [
            'question' => 'Can sellers or clients check project status without contacting support?',
            'answer' => 'Yes. Clients open Projects in their portal for status and task progress, plus invoices. They can also message the assigned seller on that order — a human thread, not a bot. Sellers reply from the seller panel on the same order. No extra Slack channel required.',
        ],
        [
            'question' => 'What notifications does Ledrix send automatically?',
            'answer' => 'Ledrix emails the right person for specific events — the list is fixed, not a DIY rules builder. Closers get mail when a lead is assigned. Clients get payment-link, payment-received, payment-failed, brief-request, and portal-invite emails. Assigned sellers and the project manager also get payment-received mail. Your team gets mail when a client opens a support ticket (and deadline reminders). Chat messages and project-status changes stay in the portal — they do not trigger a separate email today.',
        ],
        [
            'question' => 'What payment gateways does Ledrix CRM support?',
            'answer' => 'Stripe and PayPal payment links from inside the lead/order flow, generated under that client brand’s merchant keys — not a shared agency default. Paid, failed, refund, and dispute events can land back on that payment. Milestone billing and subscription billing are also supported on eligible plans.',
        ],
        [
            'question' => 'Can I import historical sales from a spreadsheet?',
            'answer' => 'Yes. Admins can upload a CSV, map columns to leads, orders, payment links, and payments, preview the plan, then commit. Ledrix does not invent missing IDs. Row and monthly upload limits follow your plan — Basic is a single-brand starter; higher tiers unlock larger and multi-brand imports.',
        ],
        [
            'question' => 'Can I use Ledrix CRM for lead management only?',
            'answer' => 'Yes. Start with brand-aware lead intake and seller routing so client lists never share a spreadsheet. Add orders and merchant payment links when you are ready to collect without mixing ledgers.',
        ],
        [
            'question' => 'Is my data isolated on Ledrix?',
            'answer' => 'Yes. Every agency gets a private workspace. Your brands, leads, orders, and clients stay separate from every other organization on the platform. Inside the workspace, commission-only sellers only see assigned records. Agencies that need a dedicated CRM database can request one. Full statement: ledrix.co/security.',
        ],
        [
            'question' => 'Is Ledrix GDPR compliant?',
            'answer' => 'Yes. Workspace owners request a ZIP export of CRM and billing CSVs with a written reason. Super Admin can erase a workspace and must log a reason. You remain the controller of client data you store; we process it to run the product. Access and correction: hello@ledrix.co. Full statement: ledrix.co/security.',
        ],
        [
            'question' => 'Can I use my own domain for my client portal?',
            'answer' => 'Yes, on plans that include custom domains. Agencies reselling to their own clients can white-label the client portal under their own domain, so buyers land on your URL instead of Ledrix. You set the hostname and verify DNS in the product.',
        ],
        [
            'question' => 'Can I send website leads into Ledrix with a script or API?',
            'answer' => 'Yes. Each brand can embed a lead script, or POST leads with a workspace token. Plans with API access also get /api/v1 (company, membership, invoices, usage) and optional lead classify — the same real-vs-junk scorer as intake. Outbound webhooks push HMAC-signed events to your URL.',
        ],
        [
            'question' => 'Does Ledrix support SSO or SCIM?',
            'answer' => 'Yes. Tell us your identity provider (Okta, Microsoft Entra, or any OIDC IdP). We turn on directory login for CRM admins and can enable SCIM so the IdP creates and deactivates those accounts. Details: ledrix.co/security.',
        ],
        [
            'question' => 'Who founded Ledrix?',
            'answer' => 'Ledrix was founded by Zeeshan Asghar, who leads product and platform direction with a focus on practical CRM tools for agencies running multiple client brands.',
        ],
        [
            'question' => 'How do I contact Ledrix for sales or support?',
            'answer' => 'Use the contact page at ledrix.co/contact-us for demos, pricing, or onboarding questions. The team typically responds within one business day.',
        ],
    ],

    'pricing_faq' => [
        [
            'question' => 'How does the Ledrix CRM free trial work?',
            'answer' => 'Choose a plan and create your agency workspace. You get full CRM access for the trial period on your package. We verify your email before activating the trial — no credit card, no charge until you decide to continue.',
        ],
        [
            'question' => 'Do I need a credit card to start a Ledrix trial?',
            'answer' => 'No. Start your free trial without entering card details. Billing starts only if you choose to continue after the trial ends.',
        ],
        [
            'question' => 'What happens after my Ledrix trial ends?',
            'answer' => 'Your tenant dashboard will prompt you to subscribe. Until then, CRM access may be limited based on subscription status. You can upgrade or change plans at any time from your workspace.',
        ],
        [
            'question' => 'How do I access the CRM after signing up?',
            'answer' => 'After email verification, sign in to your tenant dashboard and open the CRM admin panel. Your admin account is provisioned automatically with the same credentials you registered with.',
        ],
        [
            'question' => 'Is my Ledrix workspace isolated from other companies?',
            'answer' => 'Yes. Ledrix is multi-tenant SaaS — each agency workspace is isolated. Your leads, sellers, clients, and orders are scoped to your account only. Inside that workspace, client brands and merchant payments stay on the brand they belong to.',
        ],
        [
            'question' => 'Can I switch Ledrix CRM plans later?',
            'answer' => 'Yes. Contact support or use your tenant dashboard to move between plans. Limits and modules update according to your new package.',
        ],
        [
            'question' => 'Can I cancel my Ledrix subscription anytime?',
            'answer' => 'Yes. Cancel before renewal and you will not be charged for the next cycle. Your data retention policy applies after cancellation.',
        ],
        [
            'question' => 'What payment methods does Ledrix support?',
            'answer' => 'Stripe and PayPal are supported on eligible plans for tenant subscriptions and for CRM payment links generated under each client brand’s merchant. Payment setup for your Ledrix subscription is completed after trial when you choose to subscribe.',
        ],
    ],

];
