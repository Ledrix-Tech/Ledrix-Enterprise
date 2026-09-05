<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ledrix — Platform Handling Guide</title>
    <style>
        @page { margin: 28px 32px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #1e293b;
        }
        h1 { font-size: 20pt; margin: 0 0 6px; color: #0f172a; }
        h2 {
            font-size: 13pt;
            margin: 22px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #4338ca;
            color: #312e81;
            page-break-after: avoid;
        }
        h3 {
            font-size: 11pt;
            margin: 14px 0 4px;
            color: #1e3a8a;
            page-break-after: avoid;
        }
        p { margin: 0 0 8px; }
        .meta { color: #64748b; font-size: 9.5pt; margin-bottom: 16px; }
        .intro {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            margin-bottom: 16px;
        }
        .feature {
            margin-bottom: 12px;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        .feature .name { font-weight: bold; font-size: 10.5pt; margin-bottom: 4px; }
        .label {
            display: inline-block;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            min-width: 42px;
        }
        .why, .how, .when { margin: 3px 0; }
        ul { margin: 4px 0 8px 16px; padding: 0; }
        li { margin-bottom: 3px; }
        .toc { margin: 8px 0 16px; }
        .toc li { margin-bottom: 2px; }
        .footer-note {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            font-size: 9pt;
            color: #64748b;
        }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; font-size: 9.5pt; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; color: #312e81; }
    </style>
</head>
<body>
    <h1>Ledrix Platform Handling Guide</h1>
    <p class="meta">
        Completed Super Admin / platform operations features — what they are, why they matter, and how to use them.<br>
        Document date: {{ $generatedAt }} · Product: Ledrix multi-tenant SaaS CRM
    </p>

    <div class="intro">
        <p><strong>Audience:</strong> Platform owners and Super Admins who operate tenants, billing, security, and support.</p>
        <p style="margin:0"><strong>Scope:</strong> Features already shipped for central/platform handling. Each feature lists
            <strong>Why</strong> (purpose), <strong>When</strong> (typical trigger), and <strong>How</strong> (where to click / what to configure).
            CRM day-to-day lead/order work is in <code>docs/Ledrix-Tenant-Portals-Guide.pdf</code>.</p>
    </div>

    <h2>1. Where to work</h2>
    <table>
        <tr>
            <th>Area</th>
            <th>URL / entry</th>
            <th>Who</th>
        </tr>
        <tr>
            <td>Super Admin (platform)</td>
            <td><code>/super-admin</code></td>
            <td>Owner / Admin / Support roles</td>
        </tr>
        <tr>
            <td>Public marketing &amp; status</td>
            <td><code>/</code>, <code>/status</code></td>
            <td>Anyone</td>
        </tr>
        <tr>
            <td>Org portal (tenant owner)</td>
            <td>Tenant login → Organization</td>
            <td>Tenant admin</td>
        </tr>
        <tr>
            <td>Admin / Seller / Client CRM</td>
            <td><code>/admin</code>, <code>/seller</code>, <code>/client</code></td>
            <td>Tenant users</td>
        </tr>
    </table>

    <h2>2. Access, team &amp; security</h2>

    <div class="feature">
        <div class="name">Super Admin auth, roles &amp; invites</div>
        <p class="why"><span class="label">Why</span> Only trusted staff should manage tenants, payments, and erasure. Roles limit damage if an account is compromised.</p>
        <p class="how"><span class="label">How</span> Sign in at Super Admin login. Owner/Admin invite teammates (Team). Roles: Owner (full), Admin (mutations), Support (mostly read + tickets). Enable 2FA on every SA account.</p>
    </div>

    <div class="feature">
        <div class="name">Forced owner 2FA (production)</div>
        <p class="why"><span class="label">Why</span> Platform owners hold the keys to all tenants; password-only is too weak for production.</p>
        <p class="how"><span class="label">How</span> Configure force-2FA via security env/settings. After login, Owner is redirected to set up TOTP until enabled. Keep recovery codes offline.</p>
    </div>

    <div class="feature">
        <div class="name">OIDC SSO (SA + CRM Admin)</div>
        <p class="why"><span class="label">Why</span> Enterprises want IdP-controlled login (Okta, Azure AD, etc.) instead of local passwords.</p>
        <p class="how"><span class="label">How</span> Super Admin → SSO: enable, set issuer, client ID/secret, redirect URI, scopes. Users use “Sign in with SSO”. Enable SCIM (<code>SCIM_ENABLED=true</code>, <code>SCIM_BEARER_TOKEN</code>) for IdP user sync at <code>/api/scim/v2/Users</code>.</p>
    </div>

    <div class="feature">
        <div class="name">Optional 2FA — Admin, Seller, Client portals</div>
        <p class="why"><span class="label">Why</span> Reduces account takeover on CRM and client portals without blocking tenants who do not need it.</p>
        <p class="how"><span class="label">How</span> Each user enables 2FA from their profile/security page. On next login they complete a TOTP or recovery-code challenge.</p>
    </div>

    <div class="feature">
        <div class="name">SA impersonation (CRM Admin)</div>
        <p class="why"><span class="label">Why</span> Support can reproduce tenant issues without asking for passwords.</p>
        <p class="how"><span class="label">How</span> Tenant detail → Impersonate. Banner shows active session; always Stop Impersonation when done. Actions are audited.</p>
    </div>

    <h2>3. Tenants &amp; lifecycle</h2>

    <div class="feature">
        <div class="name">Tenant directory, plans, limits &amp; feature flags</div>
        <p class="why"><span class="label">Why</span> Plans productize capacity (seats, storage, modules). Overrides fix edge cases without shipping a custom build.</p>
        <p class="how"><span class="label">How</span> Super Admin → Tenants: open a company, review plan/membership, edit feature/limit overrides when needed. Prefer plan changes over permanent one-off flags.</p>
    </div>

    <div class="feature">
        <div class="name">Suspend / activate / offboard / restore</div>
        <p class="why"><span class="label">Why</span> Non-payment, abuse, or churn must stop CRM access without deleting history prematurely.</p>
        <p class="how"><span class="label">How</span> Tenant detail: Suspend (with reason), Activate, Offboard (lifecycle end), Restore if offboarded by mistake. Confirm impact before offboarding.</p>
    </div>

    <div class="feature">
        <div class="name">Storage metering &amp; usage alerts</div>
        <p class="why"><span class="label">Why</span> Storage has cost; tenants should upgrade or clean up before hard failure.</p>
        <p class="how"><span class="label">How</span> Usage is metered into central snapshots. Scheduler job emails at ~80% and 100% of plan storage; alerts clear when usage drops. Review usage on tenant/org dashboards.</p>
    </div>

    <div class="feature">
        <div class="name">Custom domain / white-label (tenant + Super Admin)</div>
        <p class="why"><span class="label">Why</span> Agencies want CRM on their own hostname for trust and branding.</p>
        <p class="when"><span class="label">When</span> Use when a tenant’s plan includes Custom Domain, or when SA provisions a hostname for an agency before go-live. Shared hosting can store + verify DNS; automatic SSL/vhost still needs VPS or Cloudflare later.</p>
        <p class="how"><span class="label">How</span> Every tenant gets <code>{slug}.ledrix.co</code> at registration (no tenant DNS). Optional BYOD: tenant enters <code>crm.theirbrand.com</code> in Organization → Connect your own domain; CNAME to <code>ledrix.co</code> and Verify. SA: Tenant detail → Workspace URL + Connect own domain. Platform ops: wildcard DNS <code>*.ledrix.co</code> + SSL.</p>
    </div>

    <h2>4. Billing &amp; payments (platform)</h2>

    <div class="feature">
        <div class="name">Payment Accounts (Stripe, Meezan primary; PayFast / JazzCash optional)</div>
        <p class="why"><span class="label">Why</span> Platform collects SaaS subscription money; credentials must live centrally and stay rotatable.</p>
        <p class="when"><span class="label">When</span> Before first paid signup. Primary rails: <strong>Pakistan → Meezan (PKR)</strong>, <strong>international → Stripe</strong> (UAE Stripe account can replace keys later without code changes). Keep PayFast/JazzCash off until you need them.</p>
        <p class="how"><span class="label">How</span> Super Admin → Payment Accounts: enable providers, paste keys/secrets, save. Never commit secrets to git. After changes, smoke-test one Stripe and one Meezan path.</p>
    </div>

    <div class="feature">
        <div class="name">Multi-currency + FX Rates (F-23)</div>
        <p class="why"><span class="label">Why</span> List prices stay in USD while tenants pay PKR, AED, EUR, or GBP. Dashboard MRR/ARR must roll up into one base currency for operators.</p>
        <p class="when"><span class="label">When</span> Launch (set USD→PKR and USD→AED). Whenever FX moves enough to matter. When onboarding UAE tenants (country AE defaults to AED). When reviewing SA dashboard revenue across regions.</p>
        <p class="how"><span class="label">How</span> Super Admin → FX Rates: add/update pairs (rate = quote units per 1 base, e.g. 1 USD = 280 PKR). Empty table seeds config defaults on first visit. Tenant billing currency comes from preferred currency or country (PK→PKR, AE→AED, else USD). Stripe Checkout charges that currency; Meezan stays PKR. Plan PKR columns win when set; otherwise USD × FX. Dashboard MRR/ARR are FX-normalized to base currency (default USD).</p>
    </div>

    <div class="feature">
        <div class="name">Stripe Checkout Subscriptions + lifecycle webhooks</div>
        <p class="why"><span class="label">Why</span> International cards should auto-renew; membership must update even if the browser never returns from Stripe.</p>
        <p class="when"><span class="label">When</span> Any non-Pakistan tenant paying by card. Also for upgrades (one-shot payment mode) and renewals issued by SA.</p>
        <p class="how"><span class="label">How</span> Tenant Billing → Pay with Stripe. Register SaaS webhook events: <code>checkout.session.completed</code>, <code>invoice.paid</code>, <code>invoice.payment_failed</code>, subscription updated/deleted. Super Admin → Webhook Events to inspect/retry. Cancel-at-period-end syncs to Stripe when a subscription ID exists.</p>
    </div>

    <div class="feature">
        <div class="name">Stripe Customer Portal (F-26)</div>
        <p class="why"><span class="label">Why</span> Tenants need self-serve card updates and Stripe invoices without opening support tickets.</p>
        <p class="when"><span class="label">When</span> After a tenant has completed at least one Stripe Checkout (has <code>stripe_customer_id</code>). Not for Meezan/PKR buyers.</p>
        <p class="how"><span class="label">How</span> Enable Customer Portal in Stripe Dashboard. Tenant Billing → <strong>Manage in Stripe</strong>. Returns to Organization Billing. Local cancel-at-period-end remains available so CRM access rules stay under Ledrix control.</p>
    </div>

    <div class="feature">
        <div class="name">Past-due CRM grace (dunning window)</div>
        <p class="why"><span class="label">Why</span> A failed auto-charge should not lock the CRM for a few days while Stripe retries.</p>
        <p class="when"><span class="label">When</span> Stripe subscription enters <code>past_due</code>. Configure days via <code>SUBSCRIPTION_PAST_DUE_GRACE_DAYS</code> (default 7). Client portal stays open even if SaaS subscription expired — only Admin/Seller CRM is billing-gated.</p>
        <p class="how"><span class="label">How</span> No SA click required for the grace itself. Watch Webhook Events for payment_failed. Issue a renewal invoice or ask the tenant to update the card in Stripe Portal if grace is ending.</p>
    </div>

    <div class="feature">
        <div class="name">Unified renewal invoice (replaces legacy approve link)</div>
        <p class="why"><span class="label">Why</span> One path for SA-issued renewals: invoice + email + Organization Billing pay link.</p>
        <p class="when"><span class="label">When</span> Tenant expired/expiring, manual follow-up, or ops wants them to pay now without waiting for scheduler mail.</p>
        <p class="how"><span class="label">How</span> Tenant detail / renewal flows → Issue renewal invoice. System picks Meezan for PKR or Stripe for international currency, emails the tenant, and they pay from Org Billing.</p>
    </div>

    <div class="feature">
        <div class="name">Self-serve plan change / proration (F-10)</div>
        <p class="why"><span class="label">Why</span> Upgrades and downgrades without SA ticket spam.</p>
        <p class="when"><span class="label">When</span> Active subscription; upgrade now (prorated invoice) or schedule change at period end / downgrade.</p>
        <p class="how"><span class="label">How</span> Organization → Plan: choose target + timing. Immediate upgrades create a payable invoice on the correct gateway. Pending changes apply on next successful renewal activation.</p>
    </div>

    <div class="feature">
        <div class="name">Void invoice + refund / billing credit (F-12)</div>
        <p class="why"><span class="label">Why</span> Mistakes, goodwill, and chargebacks need a controlled undo without deleting audit history.</p>
        <p class="when"><span class="label">When</span> Void: issued but unpaid wrong invoice. Refund: paid invoice (Stripe refund when a PaymentIntent exists; otherwise credit lands in tenant billing credits).</p>
        <p class="how"><span class="label">How</span> Tenant detail → invoice actions (Admin SA role). Confirm before refund. Credits reduce the next invoice in that currency.</p>
    </div>

    <div class="feature">
        <div class="name">SaaS invoice PDF + tax/VAT</div>
        <p class="why"><span class="label">Why</span> Finance teams need downloadable invoices; some regions need tax lines.</p>
        <p class="when"><span class="label">When</span> Tenant downloads from Billing; set <code>INVOICE_TAX_RATE</code> / <code>INVOICE_TAX_LABEL</code> when you must show VAT/GST.</p>
        <p class="how"><span class="label">How</span> PDF generated on demand (DomPDF). Tax applies at invoice creation rounding per currency (whole units for PKR, 2 dp for USD/AED).</p>
    </div>

    <div class="feature">
        <div class="name">MRR / ARR / 30d churn (dashboard)</div>
        <p class="why"><span class="label">Why</span> Operators need a weekly health snapshot of paid SaaS revenue.</p>
        <p class="when"><span class="label">When</span> Every SA dashboard visit; especially after FX rate changes or multi-region growth.</p>
        <p class="how"><span class="label">How</span> Super Admin home: MRR/ARR in base currency (FX-normalized), active paid count, 30-day churn rate. Per-currency buckets still computed internally for transparency.</p>
    </div>

    <div class="feature">
        <div class="name">Subscription payments &amp; manual confirm</div>
        <p class="why"><span class="label">Why</span> Meezan bank transfer is not instant; ops must mark paid to activate membership.</p>
        <p class="when"><span class="label">When</span> Pending bank transfer after tenant reports a transaction ID.</p>
        <p class="how"><span class="label">How</span> Super Admin → Subscription Payments: review pending, confirm when funds clear. This activates/extends the tenant subscription.</p>
    </div>

    <div class="feature">
        <div class="name">Renewal requests &amp; cancel-at-period-end</div>
        <p class="why"><span class="label">Why</span> Tenants renew or cancel without losing access mid-cycle; support needs a queue of renewal asks.</p>
        <p class="when"><span class="label">When</span> Tenant cancels auto-renew; JazzCash token renewals where that provider is on; SA reviews Renewal Requests queue.</p>
        <p class="how"><span class="label">How</span> Monitor Renewal Requests. Cancel keeps CRM until period end. Stripe cancel-at-period-end syncs when a subscription ID exists.</p>
    </div>

    <div class="feature">
        <div class="name">Invoices, due/paid emails, trial schedulers</div>
        <p class="why"><span class="label">Why</span> Clear billing history and reminders reduce churn and support tickets.</p>
        <p class="when"><span class="label">When</span> Always in production — cron must run.</p>
        <p class="how"><span class="label">How</span> System creates tenant invoices and emails on due/paid. Cron (<code>schedule:run</code>) for trial expiry and renewal reminders (7d / 3d / 1d).</p>
    </div>

    <div class="feature">
        <div class="name">Stripe platform webhook + Webhook Events UI</div>
        <p class="why"><span class="label">Why</span> Card payments must update invoices/membership even if the browser never returns.</p>
        <p class="when"><span class="label">When</span> After every Stripe Payment Accounts change; anytime a tenant says “I paid but CRM is locked”.</p>
        <p class="how"><span class="label">How</span> Register the SaaS Stripe webhook in Stripe Dashboard. Super Admin → Webhook Events: inspect failures and Retry. Keep <code>QUEUE_CONNECTION</code> non-sync in production.</p>
    </div>

    <div class="feature">
        <div class="name">Pricing packages CRUD</div>
        <p class="why"><span class="label">Why</span> Public pricing and plan entitlements must stay editable without a deploy.</p>
        <p class="when"><span class="label">When</span> New package launch, promo pricing, or PKR list-price updates (optional columns; else FX converts from USD).</p>
        <p class="how"><span class="label">How</span> Super Admin → Pricing Packages: create/edit plans, USD/PKR prices, trial days, popularity, status.</p>
    </div>

    <div class="feature">
        <div class="name">Referrals (issue / reward / expire)</div>
        <p class="why"><span class="label">Why</span> Growth loop for agencies referring peers; rewards need human approval to prevent abuse.</p>
        <p class="when"><span class="label">When</span> Campaigns or when a referred tenant converts and qualifies for credit.</p>
        <p class="how"><span class="label">How</span> Super Admin → Referrals: issue codes, mark rewarded when criteria met, expire stale ones. Credits apply per currency on the next invoice.</p>
    </div>

    <h2>5. Data protection, backup &amp; compliance</h2>

    <div class="feature">
        <div class="name">GDPR / workspace data export</div>
        <p class="why"><span class="label">Why</span> Customers can request a copy of their workspace data; regulators expect a controlled path.</p>
        <p class="how"><span class="label">How</span> Tenant requests export → Super Admin Data Exports approve/reject → ZIP of CSVs generated. Tenant download window is time-limited; purge job cleans expired files. SA can generate immediately from tenant detail.</p>
    </div>

    <div class="feature">
        <div class="name">GDPR erasure (SA)</div>
        <p class="why"><span class="label">Why</span> Right to be forgotten / contract end — remove tenant-held personal/business data when legally required.</p>
        <p class="how"><span class="label">How</span> Tenant detail → Erasure (destructive). Confirm identity and legal basis first. Prefer export backup before erase.</p>
    </div>

    <div class="feature">
        <div class="name">Per-tenant backup &amp; restore (ZIP)</div>
        <p class="why"><span class="label">Why</span> Recover from accidental wipe or bad import without restoring the whole platform.</p>
        <p class="how"><span class="label">How</span> Tenant detail → Backup. Restore supports dry-run then force. Treat restore as high-risk; run dry-run and communicate downtime.</p>
    </div>

    <div class="feature">
        <div class="name">Audit logs</div>
        <p class="why"><span class="label">Why</span> Forensics and accountability for SA and sensitive tenant actions.</p>
        <p class="how"><span class="label">How</span> Super Admin → Audit Logs. Filter by tenant/action. Clear only when retention policy allows.</p>
    </div>

    <h2>6. Integrations &amp; APIs</h2>

    <div class="feature">
        <div class="name">Tenant API tokens + Management API</div>
        <p class="why"><span class="label">Why</span> External systems need to pull company, membership, invoices, and usage without scraping the UI.</p>
        <p class="how"><span class="label">How</span> Issue tokens (org portal or SA). Call <code>/api/v1/company|membership|invoices|usage</code> with Bearer / <code>X-Api-Token</code>. Revoke leaked tokens immediately. Feature requires API access on the plan.</p>
    </div>

    <div class="feature">
        <div class="name">Outbound tenant webhooks</div>
        <p class="why"><span class="label">Why</span> Push events (e.g. invoice paid, membership activated) into Zapier, ERP, or custom backends.</p>
        <p class="how"><span class="label">How</span> Admin Organization → Webhooks: add HTTPS URL, secret, events. Platform signs payloads; consumer verifies HMAC. Check delivery history on failures.</p>
    </div>

    <h2>7. Communications &amp; trust</h2>

    <div class="feature">
        <div class="name">System announcements (+ maintenance broadcast)</div>
        <p class="why"><span class="label">Why</span> Tell all tenants (or one plan) about incidents, upgrades, or policy changes. Maintenance can pause CRM safely.</p>
        <p class="how"><span class="label">How</span> Super Admin → Announcements: title, message, type (incl. maintenance), target, window. Check “Block Admin &amp; Seller CRM” for a 503 maintenance page during windows. Prefer short windows and a matching status incident.</p>
    </div>

    <div class="feature">
        <div class="name">Public status / SLA page</div>
        <p class="why"><span class="label">Why</span> Customers expect a single place for uptime honesty; reduces “is it down?” tickets.</p>
        <p class="how"><span class="label">How</span> Public <code>/status</code>. Super Admin → Status Page: set component health, publish incidents, subscribers opt in by email. Update components when you start/end maintenance.</p>
    </div>

    <div class="feature">
        <div class="name">Website Theme</div>
        <p class="why"><span class="label">Why</span> Brand the marketing site without a front-end deploy for simple color changes.</p>
        <p class="how"><span class="label">How</span> Super Admin → Website Theme: set primary/secondary colors; preview and save. Reset restores default Ledrix indigo.</p>
    </div>

    <div class="feature">
        <div class="name">Support tickets, demos, contact queries</div>
        <p class="why"><span class="label">Why</span> Central inbox for sales and support so leads and issues are not lost in email.</p>
        <p class="how"><span class="label">How</span> Super Admin → Support Tickets (reply/status), Demo Requests, Contact Queries. Set <code>BILLING_ADMIN_EMAIL</code> for ops alerts.</p>
    </div>

    <h2>8. Org portal capabilities (tenant self-serve)</h2>
    <p>These are completed for tenant admins; platform staff should know them when guiding customers:</p>
    <ul>
        <li>Billing: Meezan PKR or Stripe multi-currency · Manage in Stripe portal · cancel / auto-renew</li>
        <li>Self-serve plan change (period-end or immediate prorated upgrade)</li>
        <li>Organization overview, team seats, plan matrix, org settings</li>
        <li>API tokens, webhooks, custom domain/white-label (feature-gated)</li>
        <li>Workspace audit log, data export request, Admin optional 2FA</li>
        <li>Dashboard health, usage limits, and platform announcements</li>
        <li>Client portal stays available when SaaS subscription is only expired (not suspended)</li>
    </ul>

    <h2>9. Daily / weekly ops checklist</h2>
    <table>
        <tr>
            <th>Cadence</th>
            <th>Actions</th>
        </tr>
        <tr>
            <td>Daily</td>
            <td>Pending Meezan payments · support tickets · open status incidents · failed Stripe webhook events</td>
        </tr>
        <tr>
            <td>Weekly</td>
            <td>Dashboard MRR/ARR · renewal requests · referral rewards · storage alert tenants · FX sanity (USD→PKR / AED) · audit suspicious SA actions</td>
        </tr>
        <tr>
            <td>Before maintenance</td>
            <td>Publish status incident · announcement with Block CRM if needed · notify key tenants</td>
        </tr>
        <tr>
            <td>After deploy</td>
            <td>Central + primary migrations · <code>optimize:clear</code> · confirm cron/queue · smoke Stripe + Meezan checkout</td>
        </tr>
    </table>

    <h2>10. Enterprise feature list (platform)</h2>
    <p>
        Ledrix is sold as an agency / closer workspace. The items below are the <strong>enterprise-style capabilities already in Super Admin</strong>
        so operators can enable them per plan or per tenant. They do not change the public security page:
        Ledrix does <strong>not</strong> claim SOC 2, ISO 27001, HIPAA, or a dedicated physical server for every customer.
    </p>
    <p>
        Companion document for day-to-day CRM: <code>docs/Ledrix-Tenant-Portals-Guide.pdf</code> (Admin, Seller, Client).
    </p>

    <h3>10.1 What Super Admin can turn on today</h3>
    <table>
        <tr>
            <th>Capability</th>
            <th>What it does</th>
            <th>How Super Admin uses it</th>
            <th>Status</th>
        </tr>
        <tr>
            <td><strong>OIDC SSO</strong></td>
            <td>IdP login (Okta, Microsoft Entra, etc.) for Super Admin and CRM Admin.</td>
            <td>Super Admin → SSO: issuer, client ID/secret, redirect URI. Users click “Sign in with SSO”.</td>
            <td>Shipped (staff-configured, not self-serve Okta screen)</td>
        </tr>
        <tr>
            <td><strong>SCIM 2.0</strong></td>
            <td>IdP can create/update CRM admin accounts at <code>/api/scim/v2/Users</code>.</td>
            <td>Set <code>SCIM_ENABLED=true</code> and <code>SCIM_BEARER_TOKEN</code>. Give the token to the customer’s IT.</td>
            <td>Shipped (env + API; no tenant self-serve portal)</td>
        </tr>
        <tr>
            <td><strong>Forced owner 2FA</strong></td>
            <td>Platform owner must enable TOTP in production.</td>
            <td>Security env / settings. Owner is redirected to 2FA setup after login.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>SA roles &amp; invites</strong></td>
            <td>Owner / Admin / Support with least-privilege for platform staff.</td>
            <td>Super Admin → Team. Support is mostly read + tickets.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Impersonation</strong></td>
            <td>Support enters a tenant CRM as admin without a password.</td>
            <td>Tenant detail → Impersonate. Stop when done. Actions are audited.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Custom domain (BYOD)</strong></td>
            <td>Agency CRM on <code>crm.theirbrand.com</code> in addition to <code>{slug}.ledrix.co</code>.</td>
            <td>Enable <code>feature_custom_domain</code> on the plan or override on Tenant detail. Verify DNS. SSL is VPS/Cloudflare (F-27-ops).</td>
            <td>App routing shipped; HTTPS on custom host is ops</td>
        </tr>
        <tr>
            <td><strong>White label</strong></td>
            <td>Replace Ledrix logo in CRM chrome with the agency brand.</td>
            <td>Plan flag <code>white_label</code>. Tenant Organization → Domain &amp; brand.</td>
            <td>Shipped (logo); hostname pairs with custom domain</td>
        </tr>
        <tr>
            <td><strong>DB-per-tenant isolation</strong></td>
            <td>Optional dedicated MySQL database for one agency’s CRM rows.</td>
            <td><code>TENANT_DB_ISOLATION=true</code> on a VPS that can <code>CREATE DATABASE</code>. Then <code>php artisan tenants:provision-db {id}</code>. Keep <code>SESSION_CONNECTION=primary</code>.</td>
            <td>Opt-in. New signups only unless F-28-migrate runs. Default production = shared primary</td>
        </tr>
        <tr>
            <td><strong>Workspace audit log</strong></td>
            <td>Tenant-visible trail of sensitive org actions; SA sees platform audit.</td>
            <td>Super Admin → Audit Logs. Tenant: Organization → Audit log.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>GDPR export</strong></td>
            <td>ZIP of workspace CSVs for the customer.</td>
            <td>Tenant requests export; SA Data Exports approve/reject, or generate from Tenant detail.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>GDPR erasure</strong></td>
            <td>Destructive removal of tenant personal/business data.</td>
            <td>Tenant detail → Erasure. Export first. Confirm legal basis.</td>
            <td>Shipped (shared-DB path; isolated DB still uses primary in some jobs)</td>
        </tr>
        <tr>
            <td><strong>Per-tenant backup / restore</strong></td>
            <td>ZIP backup of one workspace; restore with dry-run.</td>
            <td>Tenant detail → Backup / Restore. High-risk; communicate downtime.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Management API + tokens</strong></td>
            <td>Bearer access to company, membership, invoices, usage.</td>
            <td>Plan flag <code>api_access</code>. Tenant Organization → API tokens. Revoke leaks immediately.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Outbound webhooks</strong></td>
            <td>HMAC-signed events (invoice paid, membership, etc.) to the tenant’s URL.</td>
            <td>Requires API access on the plan. Organization → Webhooks.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Public status page</strong></td>
            <td>Component health and incidents at <code>/status</code>; email subscribers.</td>
            <td>Super Admin → Status Page. Pair with maintenance announcements.</td>
            <td>Shipped (informational SLA page, not a contractual credit SLA)</td>
        </tr>
        <tr>
            <td><strong>Maintenance broadcast</strong></td>
            <td>Pause Admin/Seller CRM with a 503 page during a window.</td>
            <td>Announcements → type maintenance → “Block Admin &amp; Seller CRM”.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Multi-currency + FX</strong></td>
            <td>USD list prices; tenants pay PKR, AED, EUR, GBP; MRR/ARR in one base.</td>
            <td>Super Admin → FX Rates. Tenant currency from country or preference.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Stripe Customer Portal</strong></td>
            <td>Tenant updates card and sees Stripe invoices without a ticket.</td>
            <td>Enable in Stripe Dashboard. Tenant Billing → Manage in Stripe.</td>
            <td>Shipped (after first Stripe Checkout)</td>
        </tr>
        <tr>
            <td><strong>Dunning + past-due grace</strong></td>
            <td>Failed card does not lock CRM for the grace window; ladder emails 0/3/7d.</td>
            <td><code>SUBSCRIPTION_PAST_DUE_GRACE_DAYS</code>. Watch Webhook Events.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Plan limits &amp; feature overrides</strong></td>
            <td>Seats, storage, modules per package; one-off flags for a deal.</td>
            <td>Pricing Packages for the default. Tenant detail → overrides when closing a larger contract.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Suspend / offboard / restore</strong></td>
            <td>Stop CRM access without deleting history.</td>
            <td>Tenant detail: Suspend, Activate, Offboard, Restore.</td>
            <td>Shipped</td>
        </tr>
    </table>

    <h3>10.2 Not claimed — do not sell these as live</h3>
    <table>
        <tr>
            <th>Item</th>
            <th>Why it is not “enterprise ready”</th>
        </tr>
        <tr>
            <td>SOC 2 / ISO 27001 / HIPAA</td>
            <td>No third-party audit is published. Security page states this on purpose.</td>
        </tr>
        <tr>
            <td>Signed DPA / contractual SLA with credits</td>
            <td>Ask sales; do not assume from <code>/status</code>.</td>
        </tr>
        <tr>
            <td>Self-serve Okta / Entra admin screen</td>
            <td>SSO and SCIM are turned on by Ledrix staff, not by the tenant IT admin.</td>
        </tr>
        <tr>
            <td>Dedicated physical server for every customer</td>
            <td>Default is shared <code>ledrix_primary</code> with <code>tenant_id</code> scoping. Dedicated DB is opt-in on VPS.</td>
        </tr>
        <tr>
            <td>Automatic SSL for every custom domain</td>
            <td>App accepts the host after DNS verify. Certificate/vhost is still VPS or Cloudflare.</td>
        </tr>
        <tr>
            <td>Copy existing shared CRM into a new tenant DB</td>
            <td>F-28-migrate is backlog. <code>tenants:provision-db</code> creates an empty database for existing tenants.</td>
        </tr>
    </table>

    <h2>11. Enterprise &amp; plan-gated CRM features (shipped)</h2>
    <p>
        These modules are controlled per pricing package and via Super Admin feature overrides on each tenant.
        Platform domain: <strong>ledrix.co</strong> (production <code>APP_URL</code> / <code>SEO_SITE_URL</code>).
        Enable on a plan in Pricing Packages, or override on Tenant detail when closing a larger deal.
        Tenant-facing how-to lives in the Tenant Portals guide.
    </p>
    <table>
        <tr>
            <th>Feature key</th>
            <th>What it does</th>
            <th>Where / how</th>
            <th>Status</th>
        </tr>
        <tr>
            <td><strong>PPC module</strong></td>
            <td>Core CRM: leads, orders, brands, seller assignment, pipeline.</td>
            <td>Admin / Seller CRM when plan includes module.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Stripe payments</strong></td>
            <td>Tenant Stripe keys; payment links and checkout for client orders.</td>
            <td>Admin → Account Keys; order payment links.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>PayPal payments</strong></td>
            <td>Tenant PayPal keys; PayPal checkout on orders.</td>
            <td>Admin → Account Keys.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Payment webhooks</strong></td>
            <td>Stripe/PayPal webhook processing for missed or async payments.</td>
            <td>Register tenant webhooks; platform processes capture/refund events.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Milestone payments</strong></td>
            <td>Installment / milestone payment links on orders.</td>
            <td>Order flows when feature enabled on plan.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Chargeback tracking</strong></td>
            <td>Dispute and refund webhook handling on tenant payment rails.</td>
            <td>Stripe/PayPal dispute &amp; refund webhook endpoints.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Dual invoicing</strong></td>
            <td>Advanced invoicing options for tenant client billing.</td>
            <td>CRM invoicing when plan includes feature.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Lead classification</strong></td>
            <td>AI lead scoring and classification on intake.</td>
            <td>Lead intake / classification when enabled.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Client portal</strong></td>
            <td>Client login, invoices, order visibility.</td>
            <td><code>/client</code> — stays available even when SaaS subscription expired (not suspended).</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Support tickets</strong></td>
            <td>Order-linked client support tickets.</td>
            <td>Client portal + Admin ticket handling.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Projects module</strong></td>
            <td>Project management linked to CRM work.</td>
            <td>Admin CRM when plan includes projects.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Seller leaderboard</strong></td>
            <td>Seller performance rankings.</td>
            <td>Admin / Seller dashboards when enabled.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Performance bonus</strong></td>
            <td>Bonus tracking for closers.</td>
            <td>Team / seller compensation flows.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>API access</strong></td>
            <td>Public lead/branding API and tenant Management API tokens.</td>
            <td>Org → API tokens; <code>/api/v1</code> with Bearer token.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Outbound webhooks</strong></td>
            <td>Push tenant events (invoice paid, membership, etc.) to external systems.</td>
            <td>Org → Webhooks; HMAC-signed payloads.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Custom domain</strong></td>
            <td>Agency hostname on Ledrix CRM (DNS set + verify).</td>
            <td>Org → Custom domain; SA Tenant detail. Seller login resolves verified host today.</td>
            <td>Default <code>{slug}.ledrix.co</code> automatic; optional BYOD with DNS verify</td>
        </tr>
        <tr>
            <td><strong>White label</strong></td>
            <td>Replace Ledrix logo in CRM chrome with tenant branding.</td>
            <td>Org → Branding (logo upload); Admin top bar.</td>
            <td>Shipped (logo); full hostname white-label pairs with Custom domain F-27</td>
        </tr>
        <tr>
            <td><strong>OIDC SSO</strong></td>
            <td>Enterprise IdP login for Super Admin and CRM Admin (Okta, Azure AD, etc.).</td>
            <td>Super Admin → SSO settings; “Sign in with SSO”.</td>
            <td>Shipped (login only; SCIM auto-provisioning deferred — 501 stub)</td>
        </tr>
        <tr>
            <td><strong>Multi-currency + FX</strong></td>
            <td>USD list prices; tenants pay PKR, AED, EUR, GBP; MRR/ARR normalized.</td>
            <td>Super Admin → FX Rates; tenant billing currency from country/preference.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Stripe Customer Portal</strong></td>
            <td>Self-serve card update and Stripe invoices for SaaS billing.</td>
            <td>Org Billing → Manage in Stripe (after first Stripe Checkout).</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>GDPR export / erasure</strong></td>
            <td>Workspace data ZIP export; SA destructive erasure.</td>
            <td>Org export request; SA Data Exports + Tenant detail erasure.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>Per-tenant backup / restore</strong></td>
            <td>ZIP backup and restore for one workspace (shared DB, tenant-scoped rows).</td>
            <td>SA Tenant detail → Backup / Restore (dry-run supported).</td>
            <td>Opt-in via <code>TENANT_DB_ISOLATION=true</code>; new tenants auto-provision with <code>tenants:provision-db</code></td>
        </tr>
        <tr>
            <td><strong>Workspace audit log</strong></td>
            <td>Tenant-visible audit trail for sensitive org actions.</td>
            <td>Organization → Audit log.</td>
            <td>Shipped</td>
        </tr>
        <tr>
            <td><strong>SA impersonation</strong></td>
            <td>Support enters tenant CRM as admin without passwords.</td>
            <td>SA Tenant detail → Impersonate (audited).</td>
            <td>Shipped</td>
        </tr>
    </table>

    <h2>12. Domains &amp; workspace URLs — SA runbook</h2>
    <p>Ledrix uses <strong>two domain layers</strong>. Most tenants only need layer 1.</p>

    <h3>12.1 Default workspace URL (automatic)</h3>
    <table>
        <tr><th>What</th><td><code>{slug}.ledrix.co</code> — slug is created at tenant registration from org name</td></tr>
        <tr><th>Example</th><td>Org “Tech Dev Agency” → slug <code>techdev</code> → <code>https://techdev.ledrix.co/admin</code></td></tr>
        <tr><th>Tenant DNS</th><td><strong>None.</strong> Works immediately after signup.</td></tr>
        <tr><th>Platform DNS (once)</th><td>Wildcard <code>*.ledrix.co</code> → app server or Cloudflare proxy</td></tr>
        <tr><th>Panels</th><td><code>/admin</code> · <code>/seller</code> · <code>/client</code> · <code>/tenant-profile</code></td></tr>
    </table>
    <p><strong>SA steps — new tenant</strong></p>
    <ol>
        <li>Super Admin → Tenants → open company.</li>
        <li>Note <strong>Workspace URL</strong> card: slug, hostname, panel links.</li>
        <li>Send tenant their Admin link: <code>https://{slug}.ledrix.co/admin</code>.</li>
        <li>No domain feature flag required for workspace URL — it always exists.</li>
    </ol>

    <h3>12.2 Connect own domain (advanced / enterprise)</h3>
    <p>When a tenant wants CRM on <em>their</em> domain (e.g. <code>crm.techdev.com</code> where they own <code>techdev.com</code>), not on <code>techdev.ledrix.co</code>.</p>
    <table>
        <tr><th>Plan feature</th><td><code>feature_custom_domain</code> on package, or SA override on tenant</td></tr>
        <tr><th>Tenant enters</th><td>Subdomain they control — e.g. <code>crm.techdev.com</code> (not <code>*.ledrix.co</code>)</td></tr>
        <tr><th>Tenant DNS (at GoDaddy / Cloudflare)</th><td>CNAME <code>crm.techdev.com</code> → <code>ledrix.co</code> <em>or</em> TXT verify token shown in UI</td></tr>
        <tr><th>Ledrix verifies</th><td>Organization → Verify DNS, or SA → Verify DNS on tenant detail</td></tr>
        <tr><th>After verify</th><td>Same CRM paths work on tenant hostname; session scoped to that tenant</td></tr>
        <tr><th>SSL</th><td>Tenant or SA must terminate HTTPS on custom host (Cloudflare / VPS cert)</td></tr>
    </table>
    <p><strong>SA steps — BYOD setup</strong></p>
    <ol>
        <li>Ensure plan includes Custom Domain, or save domain on tenant detail (auto-enables override).</li>
        <li>Tenant (or SA) saves hostname under <strong>Connect own domain (advanced)</strong>.</li>
        <li>Give tenant DNS instructions from tenant detail (CNAME target = platform host from <code>APP_URL</code>).</li>
        <li>After tenant adds DNS, click <strong>Verify DNS</strong>. Status → Verified.</li>
        <li>Configure SSL on custom hostname (Cloudflare orange cloud or origin cert).</li>
        <li>Smoke-test: <code>https://crm.theirbrand.com/admin/login</code> resolves correct tenant.</li>
    </ol>
    <p><strong>Common mistakes</strong></p>
    <ul>
        <li>Tenant enters <code>techdev.ledrix.co</code> in BYOD field — blocked; that URL is already automatic.</li>
        <li>Tenant enters root <code>techdev.com</code> without subdomain — prefer <code>crm.techdev.com</code> or <code>app.techdev.com</code>.</li>
        <li>DNS not propagated — wait and re-verify.</li>
    </ul>

    <h2>13. DB-per-tenant isolation — SA runbook</h2>
    <p>By default all CRM data lives in one shared <code>primary</code> database with <code>tenant_id</code> row scoping. Enterprise isolation gives each tenant a dedicated CRM database.</p>

    <h3>13.1 When to use</h3>
    <ul>
        <li>Enterprise contract requiring physical data separation.</li>
        <li>Custom domain + compliance / backup per customer.</li>
        <li>Not required for standard launch on shared DB.</li>
    </ul>

    <h3>13.2 Enable platform-wide</h3>
    <ol>
        <li>On VPS with MySQL user that can <code>CREATE DATABASE</code>, set in <code>.env</code>:
            <br><code>TENANT_DB_ISOLATION=true</code>
            <br><code>TENANT_DB_PROVISION_ON_REGISTER=true</code>
            <br><code>TENANT_DB_PREFIX=ledrix_tenant_</code> (optional)</li>
        <li>Run central migration: <code>php artisan migrate --database=central --path=database/migrations/central</code> (adds <code>tenants.crm_database</code>).</li>
        <li>New registrations auto-create <code>ledrix_tenant_{id}</code> and run CRM migrations on that DB.</li>
    </ol>

    <h3>13.3 SA steps — existing tenant</h3>
    <ol>
        <li>Super Admin → Tenants → open company.</li>
        <li>Confirm <code>TENANT_DB_ISOLATION=true</code> on server.</li>
        <li>SSH to server: <code>php artisan tenants:provision-db {tenantId}</code></li>
        <li>Tenant row gets <code>crm_database</code> (e.g. <code>ledrix_tenant_42</code>).</li>
        <li><strong>Data migration:</strong> existing rows still in shared primary until F-28-migrate tooling runs — plan maintenance window for enterprise cutover.</li>
        <li>After cutover, requests for that tenant switch CRM connection automatically via middleware.</li>
    </ol>

    <h3>13.4 What stays on central DB</h3>
    <p>Tenants, billing, memberships, invoices, packages, audit logs, API tokens registry — always on <code>central</code>. Only CRM tables (leads, orders, admins, sellers, etc.) move to tenant DB when isolated.</p>

    <h3>13.5 Backup &amp; restore</h3>
    <ul>
        <li>Shared DB tenants: SA Tenant detail → Backup (exports tenant-scoped rows from primary).</li>
        <li>Isolated DB tenants: backup includes dedicated database; restore targets that DB name on <code>tenants.crm_database</code>.</li>
    </ul>

    <h2>14. Intentionally deferred (context)</h2>
    <p>Do not treat these as required for launch with Meezan + Stripe:</p>
    <ul>
        <li>Per-tenant automatic SSL/vhost for custom domains — app routing is shipped; hosting/CDN still required for production hostnames</li>
        <li><strong>F-28-migrate</strong> — bulk migration tooling for existing tenants on shared primary DB (new tenants auto-provision when isolation is on)</li>
    </ul>

    <div class="footer-note">
        Ledrix Platform Handling Guide · Generated {{ $generatedAt }} · Keep alongside PRODUCTION.md for deploy/cron/webhook setup.
        Tenant CRM (Admin / Seller / Client): docs/Ledrix-Tenant-Portals-Guide.pdf.
        Regenerate both: <code>php artisan docs:generate-pdfs</code>.
    </div>
</body>
</html>
