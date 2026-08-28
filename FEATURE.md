# Ledrix SaaS — Remaining Features

Product backlog for gaps across **Super Admin**, **Admin (org)**, **Seller**, and **Client**.  
Ops deploy steps: [`PRODUCTION.md`](PRODUCTION.md).

**Status key**

| Tag | Meaning |
|-----|---------|
| `critical` | Before (or at) production scale |
| `soon` | Shortly after launch |
| `later` | Enterprise / nice-to-have |
| `cleanup` | Dead code / path consolidation |

When an item ships, **remove its row** from this file (do not keep `done` rows here).

---

## Already shipped (not in backlog)

**Platform / central**  
Auth/roles/2FA/invites · tenants + limits/features · Payment Accounts (Stripe, PayFast, Meezan, JazzCash) · manual payment confirm (Meezan bank transfer) · trial/subscription schedulers · renewal reminder emails (7d / 3d / 1d) · invoice HTML + due/paid emails · SaaS invoice PDF download · invoice tax/VAT via `INVOICE_TAX_RATE` · Stripe platform webhook + Webhook Events UI · **Stripe Checkout Subscriptions (auto-renew)** · invoice.paid / subscription lifecycle webhooks · unified renewal invoice (F-04) · **CRM grace while `past_due`** · **F-32 dunning ladder (0/3/7d)** · self-serve plan change / proration (F-10) · SA void invoice + refund / billing credit (F-12) · **SA custom domain set/verify/clear (F-14)** · **Multi-currency + FX rates (F-23)** · **Stripe Customer Portal (F-26)** · API tokens + `/api/v1` · outbound tenant webhooks · tenant management API · support/demos/referrals/announcements/audit · dual Admin/Tenant billing · JazzCash token auto-renew · cancel-at-period-end · tenant suspend/offboard/restore · SA impersonation · force owner 2FA · GDPR export/erasure · **F-21 export-ready email** · backup/restore · OIDC SSO · **SCIM user provisioning** · **F-27 custom-domain host routing** · **F-28 DB-per-tenant (opt-in via `TENANT_DB_ISOLATION`)** · Website Theme · storage alerts · `/status` · **F-22 status incident subscriber emails** · maintenance broadcast · F-18 cleanup · F-09 MRR/ARR/churn · **F-29 Projects UI** · **F-30 performance bonus CRUD**.

**Admin CRM org portal (A-01–A-18 — complete)**  
Expired renew path · dashboard health/usage/announcements · overview · team seats · API tokens · plan matrix + plan change · org settings · billing · Admin 2FA · custom domain self-serve · workspace audit log · Manage in Stripe portal.

**Seller / Client / Front** — messaging, portals, marketing, status.

**Stay Super-Admin-only**  
Pricing CRUD · Payment Accounts on/off · FX Rates · feature overrides · confirm Meezan · void/refund invoices · announcements · referrals · webhooks retry · demos/contacts · SA team.

**Removed**  
Payoneer SaaS billing (international = Stripe, Pakistan = Meezan). Leftover Payoneer payment rows can still be confirmed in Subscription Payments.  
**F-03** PayFast / JazzCash server ITN webhooks — not needed; SaaS billing uses Stripe (intl) and Meezan (PK). Browser return URLs for those optional providers stay as-is.

---

## Remaining features

### Ops follow-ups (enterprise)

| ID | Feature | Priority | Status | Notes |
|----|---------|----------|--------|-------|
| F-27-ops | Custom domain SSL/vhost/CDN | ops | manual | App routing is shipped; production hostnames still need VPS/Cloudflare SSL termination. |
| F-28-migrate | Migrate existing tenants to dedicated DBs | soon | backlog | New tenants auto-provision when `TENANT_DB_ISOLATION=true`. Existing shared-DB tenants need `php artisan tenants:provision-db {id}` + data migration tooling. |

---

## Suggested build order

1. **F-28-migrate** — Data migration path for tenants already on shared primary DB  
2. **F-27-ops** — SSL/vhost runbook per agency custom domain  
