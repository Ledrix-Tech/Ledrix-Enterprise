<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ledrix — Historical Sales Import</title>
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
        .callout {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            padding: 10px 12px;
            margin: 10px 0 14px;
            page-break-inside: avoid;
        }
        ul { margin: 4px 0 8px 16px; padding: 0; }
        ol { margin: 4px 0 8px 20px; padding: 0; }
        li { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; font-size: 9.5pt; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; color: #312e81; }
        .footer-note {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            font-size: 9pt;
            color: #64748b;
        }
        .muted { color: #64748b; }
        code { font-size: 9pt; }
    </style>
</head>
<body>
    <h1>Historical sales import</h1>
    <p class="meta">
        How Ledrix reconstructs past clients, orders, payment links, and payments from a spreadsheet — without inventing data.<br>
        Document date: {{ $generatedAt }} · Product: Ledrix CRM · Audience: tenant administrators
    </p>

    <div class="intro">
        <p><strong>Why this exists.</strong> Agencies moving onto Ledrix already have years of sales in Excel, Stripe exports, or another CRM. Without import, the new workspace starts empty: no past clients, no revenue history, no way to see who already paid. Import copies that history into the four records Ledrix uses every day — leads, orders, payment links, and payments — so reporting and client follow-up match the real past.</p>
        <p style="margin:0"><strong>What it is not.</strong> It is not a live Stripe sync and not a seller self-serve tool. Administrators upload a CSV, map columns, preview, then commit. Empty cells stay empty. Ledrix never fabricates Stripe or PayPal IDs.</p>
    </div>

    <h2>1. Why you need it</h2>
    <ul>
        <li><strong>Start with a full book, not a blank CRM.</strong> Past buyers, invoices, and cash collections should already be in Ledrix on day one.</li>
        <li><strong>Keep money history honest.</strong> Orders and payments drive dashboards, seller credit, and “who has paid.” History has to land in those same tables, not a side spreadsheet.</li>
        <li><strong>Your sheet does not need Ledrix column names.</strong> Exports from Excel, Stripe, or another CRM almost never match. Mapping lets you use whatever headers you have.</li>
        <li><strong>Do not invent processor IDs.</strong> Fake checkout or payment IDs would break later Stripe/PayPal matching. Cash and check rows are stored as payments with no link and no provider ID.</li>
        <li><strong>Preview before write.</strong> You see counts, unmatched brands, and duplicates before anything is created. You can roll a committed batch back.</li>
    </ul>

    <h2>2. Who can import</h2>
    <p>Administrators only. Finance accounts and sellers cannot open <code>/admin/import</code>. Sellers still see imported leads and orders in the CRM after commit; they do not upload the sheet.</p>

    <h2>3. What you need before upload</h2>
    <ul>
        <li>At least one <strong>brand</strong> (the LLC / site the sale belonged to).</li>
        <li>At least one <strong>seller</strong> — every lead in Ledrix must have an owner. Pick the historical owner, even for old records.</li>
        <li>A <strong>CSV</strong> file. In Excel: File → Save As → CSV UTF-8. .xlsx is not accepted (no Excel engine on purpose).</li>
        <li>Each contact row needs an <strong>email</strong>. Name can fall back to the email. Phone is optional.</li>
    </ul>
    <p>If you do not have a sheet yet, download the <strong>sample CSV</strong> from the import page. It includes contacts-only, invoiced-but-unpaid, and cash rows.</p>

    <h2>4. How a row becomes CRM records</h2>
    <p>Commit creates records only from data that is present. Blank cells are ignored, not filled with placeholders.</p>
    <table>
        <tr>
            <th>What is in the row</th>
            <th>What Ledrix creates</th>
        </tr>
        <tr>
            <td>Name / email (optional phone)</td>
            <td>Lead, plus a client from that contact (no portal invite)</td>
        </tr>
        <tr>
            <td>+ order amount</td>
            <td>Lead + order</td>
        </tr>
        <tr>
            <td>+ amount paid, no Stripe/PayPal link ID (cash, check, blank provider)</td>
            <td>Lead + order + payment. No payment link. Provider IDs stay empty</td>
        </tr>
        <tr>
            <td>+ Stripe or PayPal + a real provider link ID from the sheet</td>
            <td>Lead + order + payment link (and payment if amount paid is present)</td>
        </tr>
    </table>
    <div class="callout">
        A payment link is created only when the provider is <strong>stripe</strong> or <strong>paypal</strong> and the sheet has a real provider link / session ID. Ledrix may store its own internal token (<code>imp-…</code>) for the CRM link. It will not invent <code>cs_…</code> or <code>pi_…</code> values.
    </div>

    <h2>5. Step by step</h2>
    <ol>
        <li><strong>Open Import sheet</strong> (Admin sidebar, or Leads → Import sheet).</li>
        <li><strong>Choose the brand</strong> all rows belong to — or tick “Sheet spans multiple brands” (see below).</li>
        <li><strong>Assign to seller</strong> — required historical owner.</li>
        <li>Leave <strong>Enter live pipeline</strong> off for history (see below).</li>
        <li><strong>Upload the CSV</strong> → map each of your headers to a Ledrix field, or Ignore. Unmapped columns are not errors. Mapping is remembered for your next import.</li>
        <li><strong>Preview</strong> — counts for leads created/matched, orders, pay links (real ID vs unknown), payments, payments without a link, review flags, unmatched brands, duplicates. Nothing is written yet.</li>
        <li>If duplicates exist (same brand + email or phone), choose one action for the whole batch: <strong>Merge</strong>, <strong>Skip</strong>, or <strong>Create new</strong>. Duplicates are never imported silently.</li>
        <li><strong>Commit</strong>. Then check Leads / Orders / Payments. Use <strong>Roll back</strong> on that batch if the result is wrong.</li>
    </ol>

    <h2>6. The two checkboxes</h2>
    <h3>Sheet spans multiple brands</h3>
    <p><strong>Off (typical):</strong> pick one brand in the dropdown. Every imported row belongs to that brand.</p>
    <p><strong>On:</strong> the dropdown is ignored. Map a column to <strong>brand name</strong>. Each cell must match an existing brand (name match is case-insensitive). Rows that do not match are listed in preview and are <strong>not</strong> imported — they are not dropped quietly.</p>

    <h3>Enter live pipeline</h3>
    <p><strong>Off (typical for history):</strong> records are stored only. No assignment, no routing, no “new lead” emails.</p>
    <p><strong>On:</strong> each new lead is assigned to the seller you picked so it appears in the live assigned-lead workflow. Use this only if these people should be worked now, not just archived.</p>

    <h2>7. Column mapping</h2>
    <p>Your headers can be anything (“Customer”, “Mail”, “Paid $”). On the map screen, point each one at:</p>
    <table>
        <tr>
            <th>Ledrix field</th>
            <th>Use it for</th>
        </tr>
        <tr><td>Lead — name / email / phone</td><td>Who the person is. Email is required to create a lead.</td></tr>
        <tr><td>Order — amount / status</td><td>Deal size (dollars in the sheet, stored as cents). Status is optional.</td></tr>
        <tr><td>Payment link — provider / provider ID</td><td>stripe or paypal, plus the real session / link ID from the processor if you have it.</td></tr>
        <tr><td>Payment — amount paid / paid at / provider payment ID</td><td>Money collected, when, and the real <code>pi_…</code> / transaction ID if the sheet has one.</td></tr>
        <tr><td>Brand name</td><td>Only for multi-brand sheets.</td></tr>
        <tr><td>Ignore</td><td>Notes, UTMs, extra columns. Not an error.</td></tr>
    </table>
    <p>Amounts in the sheet are treated as <strong>dollars</strong> (for example 1500.00 → $1,500.00), not cents.</p>

    <h2>8. Duplicates, review, rollback</h2>
    <ul>
        <li><strong>Duplicate</strong> means same brand and the same email (or same phone). Preview lists them. You must choose merge, skip, or create new for the whole file.</li>
        <li><strong>Merge</strong> adds new orders/payments onto the existing lead. It does not overwrite the old contact blindly.</li>
        <li><strong>Review flags</strong> mark imported processor IDs that Ledrix did not verify with Stripe/PayPal, or unknown providers / link IDs it could not store as a pay link.</li>
        <li><strong>Rollback</strong> removes that batch’s payments, then pay links, then orders, then imported leads that have no other remaining orders. Clients created as contacts are left in place.</li>
    </ul>

    <h2>9. What import will never do</h2>
    <ul>
        <li>Invent Stripe/PayPal session or payment IDs.</li>
        <li>Create a payment link only so a payment has a parent — cash payments keep <code>payment_link_id</code> empty.</li>
        <li>Create empty orders, links, or payments just to satisfy foreign keys.</li>
        <li>Email clients or open a client portal.</li>
        <li>Route leads unless “Enter live pipeline” is on.</li>
    </ul>

    <p class="footer-note">
        Path: Admin → Import sheet · Sample CSV and this guide are both available on that page.<br>
        Ledrix CRM · Historical sales import
    </p>
</body>
</html>
