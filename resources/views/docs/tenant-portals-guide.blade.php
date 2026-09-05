<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ledrix — Tenant Portals Guide</title>
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
        ol { margin: 4px 0 8px 20px; padding: 0; }
        li { margin-bottom: 3px; }
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
        code { font-size: 9pt; }
    </style>
</head>
<body>
    <h1>Ledrix Tenant Portals Guide</h1>
    <p class="meta">
        Admin, Seller, and Client — every workspace feature and how it works.<br>
        Document date: {{ $generatedAt }} · Product: Ledrix multi-tenant SaaS CRM
    </p>

    <div class="intro">
        <p><strong>Audience:</strong> Agency owners, CRM admins, closers (sellers), project managers, and the agency’s clients.</p>
        <p style="margin:0"><strong>Scope:</strong> What each portal can do, why it exists, and where to click.
            Super Admin / billing-of-Ledrix is in <code>docs/Ledrix-Platform-Handling-Guide.pdf</code>.
            Some items only appear when the workspace plan includes that feature.</p>
    </div>

    <h2>1. Three portals, one workspace</h2>
    <table>
        <tr>
            <th>Portal</th>
            <th>URL</th>
            <th>Who</th>
            <th>Job</th>
        </tr>
        <tr>
            <td>Admin CRM + Organization</td>
            <td><code>/admin</code></td>
            <td>Workspace owner and org admins</td>
            <td>Run the agency: brands, sellers, leads, orders, keys, billing of Ledrix, team</td>
        </tr>
        <tr>
            <td>Seller</td>
            <td><code>/seller</code></td>
            <td>Closers and project managers</td>
            <td>Own leads, send payment links, briefs, messages, delivery</td>
        </tr>
        <tr>
            <td>Client</td>
            <td><code>/client</code></td>
            <td>The agency’s paying customers</td>
            <td>See invoices, pay, send briefs, tickets, messages</td>
        </tr>
    </table>
    <p>
        Default host is <code>https://{slug}.ledrix.co</code> (created at signup). Example:
        <code>https://techdev.ledrix.co/admin</code>. On a verified custom domain the same paths work
        (<code>https://crm.theirbrand.com/admin</code>). Local IP URLs use <code>{slug}.localhost:8000</code>.
    </p>
    <p>
        Isolation: another Ledrix customer cannot open this workspace. Inside the workspace, sellers see their book;
        clients see only their own orders. Super Admin is a separate login at <code>/super-admin</code> and is not for agency staff.
    </p>

    <h2>2. How a sale moves through Ledrix</h2>
    <ol>
        <li><strong>Lead in</strong> — website script, API, manual add, or historical import.</li>
        <li><strong>Assign a closer</strong> — Admin or routing puts the lead on a seller and a brand.</li>
        <li><strong>Close</strong> — seller opens the lead, sends a Stripe/PayPal payment link (or milestone links).</li>
        <li><strong>Order + payment</strong> — client pays on <code>/pay/now/{token}</code>; webhook records the payment.</li>
        <li><strong>Client access</strong> — Admin sets a client password; client gets a portal email.</li>
        <li><strong>Brief → project → ticket</strong> — client fills a brief; PM/seller deliver; client can raise tickets and message.</li>
        <li><strong>Renew</strong> — seller/admin send a renew-order link; new order rows stay under the same client.</li>
    </ol>

    <h2>3. Admin CRM</h2>
    <p>Sign in at <code>/admin/login</code>. Optional 2FA from the admin profile. SSO is available when Ledrix staff have enabled OIDC for the workspace.</p>

    <div class="feature">
        <div class="name">Dashboard</div>
        <p class="why"><span class="label">Why</span> One screen for pipeline health: brands, open leads, orders, and payment keys.</p>
        <p class="how"><span class="label">How</span> Admin → Dashboard. KPI cards jump to Brands, Leads, Orders, Account Keys.</p>
    </div>

    <div class="feature">
        <div class="name">Brands</div>
        <p class="why"><span class="label">Why</span> Agencies run more than one offer or LLC. Each brand keeps its own pipeline, payment keys, and script.</p>
        <p class="how"><span class="label">How</span> Admin → Brands: create name/status. Attach Stripe/PayPal keys per brand under Account Keys. Leads and orders always belong to a brand.</p>
    </div>

    <div class="feature">
        <div class="name">Sellers (closers)</div>
        <p class="why"><span class="label">Why</span> Closers need their own login and a book that is not a shared spreadsheet.</p>
        <p class="how"><span class="label">How</span> Admin → Sellers: create seller, set status, open Performance. Optional Performance bonus (plan flag) on the seller record. Inactive sellers cannot sign in.</p>
    </div>

    <div class="feature">
        <div class="name">Leads</div>
        <p class="why"><span class="label">Why</span> Every inbound contact must land on a named closer and brand while it is still hot.</p>
        <p class="how"><span class="label">How</span> Admin → Leads: list, open details, change status, assign seller (Lead assign). Delete single or bulk. Optional lead classification scores junk vs real when that plan feature is on.</p>
        <p class="when"><span class="label">When</span> After a form/API hit, after import, or when a closer asks “who owns this?”</p>
    </div>

    <div class="feature">
        <div class="name">Historical sales import</div>
        <p class="why"><span class="label">Why</span> A new workspace should not start empty. Past clients, orders, and cash must land in the same tables the CRM uses every day.</p>
        <p class="how"><span class="label">How</span> Admin → Import sheet: upload CSV → Map columns → Preview → Commit. Download the sample CSV and the import PDF from that page. Empty cells stay empty. You can roll a committed batch back. Do not re-upload the same file after a successful commit.</p>
    </div>

    <div class="feature">
        <div class="name">Clients</div>
        <p class="why"><span class="label">Why</span> A client is the person (or company) who bought — not the lead row. Portal login, briefs, and invoices hang off this record.</p>
        <p class="how"><span class="label">How</span> Admin → Clients: status, delete. <strong>Client account access</strong> (plan: client portal): set email/password; Ledrix emails the portal link immediately. Client stays able to log in even if the agency’s Ledrix subscription is only expired (not suspended).</p>
    </div>

    <div class="feature">
        <div class="name">Orders</div>
        <p class="why"><span class="label">Why</span> The commercial record: what was sold, to whom, under which brand, and whether it was renewed.</p>
        <p class="how"><span class="label">How</span> Admin → Orders. Open an order to generate a payment link, send a renew-order link, raise/see tickets, or generate a client invoice (dual invoicing feature). Renewals list is under Renewed orders.</p>
    </div>

    <div class="feature">
        <div class="name">Payment links (Stripe / PayPal)</div>
        <p class="why"><span class="label">Why</span> Closers collect on the call. The client pays a hosted Ledrix page; the CRM records the payment without a shared Stripe login.</p>
        <p class="how"><span class="label">How</span> From a lead/order: Generate link. Choose amount, currency, optional milestones. Client opens <code>/pay/now/{token}</code>. Webhooks mark paid. Toggle pay-link status if you must void a link. Requires Account Keys on the brand and the Stripe/PayPal plan flags.</p>
    </div>

    <div class="feature">
        <div class="name">Account Keys</div>
        <p class="why"><span class="label">Why</span> Each brand uses the agency’s own Stripe/PayPal — Ledrix does not take the client payment.</p>
        <p class="how"><span class="label">How</span> Admin → Account Keys: paste live keys per brand. Register the brand’s webhook URLs in Stripe/PayPal so missed checkouts still land. Never share keys in chat.</p>
    </div>

    <div class="feature">
        <div class="name">Payments list</div>
        <p class="why"><span class="label">Why</span> Finance needs a ledger of captured client payments, refunds, and disputes.</p>
        <p class="how"><span class="label">How</span> Admin → Payments (when Stripe or PayPal is on the plan). Chargeback/refund rows appear from provider webhooks.</p>
    </div>

    <div class="feature">
        <div class="name">Domain scripts (lead capture)</div>
        <p class="why"><span class="label">Why</span> Website and landing-page leads should hit Ledrix, not an inbox.</p>
        <p class="how"><span class="label">How</span> Admin → Script (API access on plan): copy the brand snippet, paste on the site, Test lead, check script status. POST to the lead API if you do not want a script.</p>
    </div>

    <div class="feature">
        <div class="name">Projects &amp; tasks</div>
        <p class="why"><span class="label">Why</span> After the close, delivery needs a place that is not Slack.</p>
        <p class="how"><span class="label">How</span> Admin → Projects (plan flag): create project, tasks, update status. Clients can view the same project in their portal when linked.</p>
    </div>

    <div class="feature">
        <div class="name">Support tickets (order-linked)</div>
        <p class="why"><span class="label">Why</span> Client issues stay on the order, not in a personal inbox.</p>
        <p class="how"><span class="label">How</span> From an order → Tickets. Change status. Clients open tickets from <code>/client</code>. Deadline checks run on the shared schedule.</p>
    </div>

    <div class="feature">
        <div class="name">CSV export</div>
        <p class="why"><span class="label">Why</span> Ops and accountants need a dump of leads, clients, orders, payments, or tickets.</p>
        <p class="how"><span class="label">How</span> Admin export routes for those tables. For a full legal pack use Organization → Data export instead.</p>
    </div>

    <div class="feature">
        <div class="name">Admin roles inside CRM</div>
        <p class="why"><span class="label">Why</span> Not every staff user should see keys or billing.</p>
        <p class="how"><span class="label">How</span> Full Admin sees the Organization block and keys. Finance-only users see Brand Payments / Brand Payouts. Project managers see Assigned leads and delivery orders, not the full lead pile.</p>
    </div>

    <h2>4. Admin — Organization (Ledrix subscription)</h2>
    <p>This is the agency paying <em>Ledrix</em>, not the agency charging its clients. Open from Admin → Organization (or <code>/tenant-profile</code> after tenant login).</p>

    <div class="feature">
        <div class="name">Overview, plan &amp; features</div>
        <p class="why"><span class="label">Why</span> See seats, storage, and which modules the package includes.</p>
        <p class="how"><span class="label">How</span> Organization → Overview / Plan &amp; features. Change plan: upgrade now (prorated invoice) or schedule at period end / downgrade.</p>
    </div>

    <div class="feature">
        <div class="name">Org settings</div>
        <p class="why"><span class="label">Why</span> Company name and workspace profile must stay accurate for invoices and the public slug.</p>
        <p class="how"><span class="label">How</span> Organization → Org settings. Save. Slug / workspace URL is assigned at registration.</p>
    </div>

    <div class="feature">
        <div class="name">Team seats</div>
        <p class="why"><span class="label">Why</span> Extra org admins without sharing the owner password.</p>
        <p class="how"><span class="label">How</span> Organization → Team: invite / remove. Seat count is limited by the plan.</p>
    </div>

    <div class="feature">
        <div class="name">Billing Ledrix (Stripe or Meezan)</div>
        <p class="why"><span class="label">Why</span> Keep the workspace paid so Admin/Seller CRM stays open.</p>
        <p class="how"><span class="label">How</span> Organization → Billing: pay invoice, download SaaS invoice PDF, set currency, auto-renew, cancel at period end. International cards → Stripe Checkout, then Manage in Stripe. Pakistan → Meezan bank transfer (report the transaction ID; Super Admin confirms). Client portal stays up if the SaaS sub is only expired.</p>
    </div>

    <div class="feature">
        <div class="name">Domain &amp; brand</div>
        <p class="why"><span class="label">Why</span> Agencies want CRM on their hostname and logo, not only <code>slug.ledrix.co</code>.</p>
        <p class="how"><span class="label">How</span> Organization → Domain &amp; brand (custom domain / white-label on plan): save <code>crm.theirbrand.com</code>, add CNAME or TXT at their DNS, Verify. Upload logo. HTTPS on that host needs Cloudflare or a VPS cert — the app only accepts the host after verify.</p>
    </div>

    <div class="feature">
        <div class="name">Audit log, data export, API, webhooks</div>
        <p class="why"><span class="label">Why</span> Accountability, GDPR copy, and wiring Ledrix into the agency’s other tools.</p>
        <p class="how"><span class="label">How</span> Audit log: read-only trail. Data export: request ZIP; download when Super Admin approves (or SA generates it). API tokens: Bearer / <code>X-Api-Token</code> for <code>/api/v1</code>. Webhooks: HTTPS URL + secret; verify HMAC. Revoke tokens if leaked.</p>
    </div>

    <div class="feature">
        <div class="name">Platform support &amp; referrals</div>
        <p class="why"><span class="label">Why</span> Talk to Ledrix without leaving the workspace; reward peers who sign up.</p>
        <p class="how"><span class="label">How</span> Organization → Support: new ticket, reply. Referrals: issue a code; Super Admin marks the reward when it qualifies.</p>
    </div>

    <h2>5. Seller portal</h2>
    <p>Sign in at <code>/seller/login</code>. Optional 2FA. The closer sees their book — not every lead in the agency.</p>

    <div class="feature">
        <div class="name">Dashboard &amp; performance</div>
        <p class="why"><span class="label">Why</span> Closers need today’s book and their own numbers, not an admin warehouse.</p>
        <p class="how"><span class="label">How</span> Seller → Dashboard and Performance. Leaderboard appears when that plan feature is on.</p>
    </div>

    <div class="feature">
        <div class="name">Leads (front seller)</div>
        <p class="why"><span class="label">Why</span> Own the lead, change status, finish into an order, send a pay link.</p>
        <p class="how"><span class="label">How</span> Seller → Leads → details. Update status, assign if allowed, Finish lead to create the order, then Generate payment link. Do not work other sellers’ rows.</p>
    </div>

    <div class="feature">
        <div class="name">Assigned leads (project manager)</div>
        <p class="why"><span class="label">Why</span> After the close, a PM works delivery, not the full inbound pile.</p>
        <p class="how"><span class="label">How</span> Seller (PM role) → Assigned Leads and Orders. Same tickets/projects/briefs as the closer path, scoped to assigned work.</p>
    </div>

    <div class="feature">
        <div class="name">Clients, briefs, messages</div>
        <p class="why"><span class="label">Why</span> The closer stays on the relationship: intake form, chat on the order, status of the brief.</p>
        <p class="how"><span class="label">How</span> Seller → Clients / Briefs / Messages. Send or review a brief link; client can also fill <code>/client/brief/{token}</code> without logging in. Message threads are per order. Client sees the same thread in their portal.</p>
    </div>

    <div class="feature">
        <div class="name">Orders, renewals, payments</div>
        <p class="why"><span class="label">Why</span> Send another charge or a renewal without asking Admin for a Stripe login.</p>
        <p class="how"><span class="label">How</span> Seller → Orders: generate or renew payment link, open tickets, generate invoice if dual invoicing is on. Payments list when Stripe/PayPal is on the plan.</p>
    </div>

    <div class="feature">
        <div class="name">Brands, sellers, projects</div>
        <p class="why"><span class="label">Why</span> Some agencies let closers add a brand or see teammates; delivery uses Projects.</p>
        <p class="how"><span class="label">How</span> Seller → Brands / Sellers / Projects when those menus show. Domain scripts are read-oriented for sellers when API access is on. Admin still owns Account Keys.</p>
    </div>

    <h2>6. Client portal</h2>
    <p>
        Sign in at <code>/client/login</code> after Admin sets account access. Optional 2FA.
        This portal stays available if the agency’s Ledrix subscription is expired — only Admin/Seller CRM is billing-gated
        (unless the workspace is suspended).
    </p>

    <div class="feature">
        <div class="name">Dashboard</div>
        <p class="why"><span class="label">Why</span> The buyer should see their work with this agency in one place.</p>
        <p class="how"><span class="label">How</span> Client → Dashboard after login. Jump to briefs, invoices, tickets, projects, messages.</p>
    </div>

    <div class="feature">
        <div class="name">Invoices &amp; paying</div>
        <p class="why"><span class="label">Why</span> Clients need the bill and a way to pay without a sales call every time.</p>
        <p class="how"><span class="label">How</span> Client → Invoices → open details. Payment is usually the pay-link the closer sent (<code>/pay/now/...</code>) or the invoice page when dual invoicing is on. Profile is under the user menu (update name/contact).</p>
    </div>

    <div class="feature">
        <div class="name">Briefs</div>
        <p class="why"><span class="label">Why</span> Delivery starts from what the client wrote, not from a forgotten email.</p>
        <p class="how"><span class="label">How</span> Logged-in: Client → Briefs, submit the form. Token link <code>/client/brief/{token}</code> works without login when the seller sent that URL.</p>
    </div>

    <div class="feature">
        <div class="name">Projects</div>
        <p class="why"><span class="label">Why</span> Client can see progress without sitting in the agency CRM.</p>
        <p class="how"><span class="label">How</span> Client → Projects → open one. Read-only vs agency editing; Admin/Seller own the tasks.</p>
    </div>

    <div class="feature">
        <div class="name">Tickets</div>
        <p class="why"><span class="label">Why</span> Issues stay on the order so the closer and PM see them.</p>
        <p class="how"><span class="label">How</span> Client → Tickets, or Raise ticket from an order. Agency updates status from Admin/Seller order tickets.</p>
    </div>

    <div class="feature">
        <div class="name">Messages</div>
        <p class="why"><span class="label">Why</span> Short thread on the order instead of a lost WhatsApp chat.</p>
        <p class="how"><span class="label">How</span> Client → Messages; seller sees the same thread under Seller → Messages.</p>
    </div>

    <h2>7. Plan-gated features the tenant actually sees</h2>
    <p>If a menu is missing, the package does not include it. Ask the workspace owner to upgrade, or Super Admin can override on the tenant.</p>
    <table>
        <tr>
            <th>Plan feature</th>
            <th>Who sees it</th>
            <th>What they do</th>
        </tr>
        <tr>
            <td>PPC module</td>
            <td>Admin, Seller</td>
            <td>Core CRM (leads, orders, brands). Required for those portals.</td>
        </tr>
        <tr>
            <td>Stripe / PayPal</td>
            <td>Admin, Seller, client pay page</td>
            <td>Account Keys, payment links, Payments list, webhooks.</td>
        </tr>
        <tr>
            <td>Milestone payments</td>
            <td>Admin, Seller</td>
            <td>Split a deal into installment links.</td>
        </tr>
        <tr>
            <td>Dual invoicing</td>
            <td>Admin, Seller, Client</td>
            <td>Generate a client invoice from an order.</td>
        </tr>
        <tr>
            <td>Client portal</td>
            <td>Admin (set password), Client</td>
            <td>Client login, invoices, briefs, tickets, messages.</td>
        </tr>
        <tr>
            <td>Support tickets</td>
            <td>All three</td>
            <td>Order-linked tickets.</td>
        </tr>
        <tr>
            <td>Projects</td>
            <td>Admin, Seller, Client</td>
            <td>Projects and tasks; client can view.</td>
        </tr>
        <tr>
            <td>Lead classification</td>
            <td>Admin intake</td>
            <td>Score real vs junk. Not a bot that writes emails.</td>
        </tr>
        <tr>
            <td>Seller leaderboard</td>
            <td>Seller</td>
            <td>Rankings among closers.</td>
        </tr>
        <tr>
            <td>Performance bonus</td>
            <td>Admin</td>
            <td>Bonus rows on a seller’s performance page.</td>
        </tr>
        <tr>
            <td>API access</td>
            <td>Admin</td>
            <td>Domain scripts, API tokens, outbound webhooks.</td>
        </tr>
        <tr>
            <td>Custom domain / white label</td>
            <td>Admin Organization</td>
            <td>Own hostname + logo.</td>
        </tr>
    </table>

    <h2>8. What tenants do not manage</h2>
    <ul>
        <li>Ledrix Super Admin, platform Stripe/Meezan keys, FX rates, public pricing packages.</li>
        <li>SOC 2 / ISO / HIPAA claims — not part of the product; see the public Security page.</li>
        <li>Creating a dedicated MySQL database — Super Admin / VPS only, optional, off by default.</li>
        <li>SSL certificates for a custom domain — agency DNS + Cloudflare/VPS, not a button in CRM.</li>
    </ul>

    <div class="footer-note">
        Ledrix Tenant Portals Guide · Generated {{ $generatedAt }} ·
        Platform / Super Admin: docs/Ledrix-Platform-Handling-Guide.pdf ·
        Import detail: Admin → Import sheet → How import works (PDF).
        Regenerate: <code>php artisan docs:generate-pdfs</code>.
    </div>
</body>
</html>
