<?php

return [

    'site_name' => 'Ledrix CRM',

    'default_title' => 'Ledrix CRM — Sales CRM for Closers Who Get Paid Faster',

    'default_description' => 'Ledrix is the sales CRM closers open first: instant lead ownership, a focused seller panel, and Stripe or PayPal payment links from the lead card. Free trial, no card.',

    'default_keywords' => 'Ledrix CRM, sales CRM for closers, closer CRM, seller panel, payment links CRM, Stripe CRM, lead routing, multi-brand CRM, agency sales CRM, stop dropped leads',

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
        'title'        => 'Ledrix CRM — 60-second overview for closers, founders, and agencies',
        'description'  => 'Watch a closer take an inbound lead to a payment link in Ledrix: seller panel, brand routing, and Stripe or PayPal from the lead card — before you sign up.',
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
            'origin' => 'While working with agencies and sales teams, Zeeshan Asghar noticed a familiar pattern: closers were drowning in spreadsheets, payment links lived in one tool, leads in another, and client updates in a third. Closers did not need more features — they needed one workspace that matched how a deal actually closes.',
            'founding' => 'In 2024, he founded Ledrix to help revenue teams grow with structure instead of chaos — capturing leads, assigning sellers, closing orders, and collecting payments in a tenant-isolated CRM built for agencies from day one.',
            'today' => 'Ledrix has expanded into a crafted, not cobbled platform: multi-brand workspaces, seller and client portals, Stripe and PayPal flows, and automation-ready architecture. Led by Zeeshan, the team is building the practical sales operating system scaling agencies expect today — and the intelligent CRM they will need tomorrow.',
        ],
        'description' => 'Zeeshan Asghar founded Ledrix to build a sales CRM closers will actually use — combining lead ownership, a focused seller panel, and payment links with modern SaaS architecture.',
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
            'answer' => 'Ledrix is a sales CRM for closers, founders, and agencies. It routes every inbound lead to a closer, opens a focused seller panel (their book only), and lets them send Stripe or PayPal payment links from the lead card — while the buyer is still on the call.',
        ],
        [
            'question' => 'What does a sales closer get in Ledrix?',
            'answer' => 'A seller panel with assigned leads, follow-ups, orders, and the payment link on that deal. Closers cannot browse the company database or cherry-pick someone else’s book. They get mail when a lead is assigned, and they reply to clients on the order — not in Slack.',
        ],
        [
            'question' => 'Can I send a payment link while I am still on the call?',
            'answer' => 'Yes. Generate Stripe or PayPal from the lead card in seconds after the buyer says yes. You do not hop to a separate billing tab mid-close. Paid and failed updates can land back on the order so you know if it went through.',
        ],
        [
            'question' => 'Who is Ledrix CRM for?',
            'answer' => 'Closers who want a focused pipeline, founders scaling a sales team, and agency owners running multiple brands — without paying for HubSpot + Stripe + Slack as three disconnected steps.',
        ],
        [
            'question' => 'Is Ledrix CRM free to try?',
            'answer' => 'Yes. Open a plan-based free trial with a real seller panel and admin CRM — no credit card required. Watch the 60-second demo first if you want the tour before signup.',
        ],
        [
            'question' => 'How is Ledrix different from other CRM software?',
            'answer' => 'Most CRMs are contact databases closers avoid. Ledrix is built around the close: instant lead ownership, a stripped-down seller panel, payment links in seconds after a yes, and unlimited brands under one login — not hours later in another app.',
        ],
        [
            'question' => 'Does Ledrix support multiple brands or teams?',
            'answer' => 'Yes. Run every brand under one account without mixing pipelines. Admins see the full picture; sellers only see their assignments; clients get a secure portal.',
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
            'answer' => 'Stripe and PayPal payment links from inside the lead/order flow — so your closer can collect while the buyer is still hot. Milestone billing and subscription billing are also supported on eligible plans.',
        ],
        [
            'question' => 'Can I import historical sales from a spreadsheet?',
            'answer' => 'Yes. Admins can upload a CSV, map columns to leads, orders, payment links, and payments, preview the plan, then commit. Ledrix does not invent missing IDs. Row and monthly upload limits follow your plan — Basic is a single-brand starter; higher tiers unlock larger and multi-brand imports.',
        ],
        [
            'question' => 'Can I use Ledrix CRM for lead management only?',
            'answer' => 'Yes. Start with lead intake and seller routing so nothing sits in a shared spreadsheet. Add orders and payment links when you’re ready to close faster.',
        ],
        [
            'question' => 'Is my data isolated on Ledrix?',
            'answer' => 'Yes. Every company gets a private workspace. Your brands, leads, orders, and clients stay separate from every other organization on the platform.',
        ],
        [
            'question' => 'Is Ledrix GDPR compliant?',
            'answer' => 'Ledrix supports GDPR-style requests. Workspaces are tenant-scoped so other companies cannot see your data. Workspace owners can export their CRM data. You can request access, correction, or erasure of personal data we hold by emailing hello@ledrix.co. We process your clients’ data as a processor when you store it in Ledrix. We do not claim a third-party GDPR certification. Full statement: ledrix.co/security.',
        ],
        [
            'question' => 'Can I use my own domain for my client portal?',
            'answer' => 'Yes, on plans that include custom domains. Agencies reselling to their own clients can white-label the client portal under their own domain, so buyers land on your URL instead of Ledrix. You set the hostname and verify DNS in the product.',
        ],
        [
            'question' => 'Can I send website leads into Ledrix with a script or API?',
            'answer' => 'Yes. Each brand can embed a lead script on your site, or you can POST leads to the API with a workspace token. The lead still routes to a closer and a brand. API access and lead scoring are plan features.',
        ],
        [
            'question' => 'Does Ledrix support SSO or SCIM?',
            'answer' => 'OIDC sign-in for CRM admins and SCIM 2.0 admin provisioning exist when we enable them for your workspace. They are not a self-serve setting in the trial. Ask sales if you need them. Details: ledrix.co/security.',
        ],
        [
            'question' => 'Who founded Ledrix?',
            'answer' => 'Ledrix was founded by Zeeshan Asghar, who leads product and platform direction with a focus on practical CRM tools for modern sales teams and agencies.',
        ],
        [
            'question' => 'How do I contact Ledrix for sales or support?',
            'answer' => 'Use the contact page at ledrix.co/contact-us for demos, pricing, or onboarding questions. The team typically responds within one business day.',
        ],
    ],

    'pricing_faq' => [
        [
            'question' => 'How does the Ledrix CRM free trial work?',
            'answer' => 'Choose a plan and create your workspace. You get full CRM access for the trial period on your package. We verify your email before activating the trial — no payment is collected upfront.',
        ],
        [
            'question' => 'Do I need a credit card to start a Ledrix trial?',
            'answer' => 'No. You can start your free trial without entering card details. Billing is only required when you choose to continue after the trial ends.',
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
            'answer' => 'Yes. Ledrix is multi-tenant SaaS — each workspace has its own tenant ID. Your leads, sellers, clients, and orders are scoped to your account only.',
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
            'answer' => 'Stripe and PayPal are supported on eligible plans for tenant subscriptions and CRM payment links. Payment setup is completed after trial when you choose to subscribe.',
        ],
    ],

];
