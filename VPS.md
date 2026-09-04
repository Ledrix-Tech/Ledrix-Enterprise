# Ledrix — VPS go-live (email + ops)

Use this when you move off Namecheap shared hosting onto a VPS.  
Deploy steps and `.env` keys: [`PRODUCTION.md`](PRODUCTION.md). Product backlog: [`FEATURE.md`](FEATURE.md).

**Today (shared hosting):** mail is sent **immediately** via `SafeMail` so SMTP never blocks a payment, ticket, or password save. Cron every 5 minutes only runs *when* reminders are due (renewal, dunning, trial).

**On VPS:** keep `SafeMail`. Add a real always-on worker for jobs (exports, webhooks, ML). Switch cron to **every minute**. Use a proper mail provider and DNS auth so inbox delivery is enterprise-grade.

---

## 1. Email notifications — set these on the VPS

### Mailbox / provider

- [ ] Use a real ESP, not the VPS `sendmail` binary: **Amazon SES**, **Postmark**, **Mailgun**, or **Resend**
- [ ] Dedicated sending domain or subdomain: `mail.ledrix.co` or `ledrix.co`
- [ ] From address is a real mailbox the ESP allows: `MAIL_FROM_ADDRESS=no-reply@ledrix.co`
- [ ] `MAIL_FROM_NAME=Ledrix`
- [ ] Ops inbox: `BILLING_ADMIN_EMAIL` (support tickets, bank-transfer reports, platform alerts)
- [ ] `MAIL_MAILER=smtp` (or the ESP’s Laravel mailer if you add one later)
- [ ] `MAIL_HOST` / `MAIL_PORT=587` / `MAIL_ENCRYPTION=tls` / username + password from the ESP
- [ ] `MAIL_VERIFY_PEER=true` (do **not** leave the shared-hosting bypass on)
- [ ] Quote passwords that contain `#` or `@`: `MAIL_PASSWORD="..."`

### DNS so mail is not junk

Publish these on the sending domain (ESP dashboards give the exact values):

- [ ] **SPF** — allow the ESP (and only the ESP) to send as `@ledrix.co`
- [ ] **DKIM** — ESP-generated CNAME/TXT
- [ ] **DMARC** — start with `p=none` + rua mailbox, tighten later
- [ ] Reverse DNS / PTR on the VPS **only if** you send from the box itself (skip if using SES/Postmark)
- [ ] Warm up a new domain (low volume first week); do not blast from a cold IP/domain

### App mail behaviour (already in code)

No extra feature work required. After SMTP + DNS are correct, these send on the live action or on the daily/hourly scheduler:

| When | Who gets mail |
|------|----------------|
| Signup / resend verify | Tenant |
| Team invite | New org seat |
| Invoice due / paid / void / refund | Tenant |
| Trial ending, renewal 7/3/1d, dunning 0/3/7, **expired** | Tenant |
| Meezan transfer reported | Ops (`BILLING_ADMIN_EMAIL`) |
| Platform support open / reply | Ops + tenant |
| Status incident | Status subscribers |
| Client portal password, pay link, payment success/fail | Client (and sellers where wired) |
| Lead / ticket / brief / risky-client | CRM staff + prospect |

`SafeMail` never fails the business action if SMTP is down. Check `storage/logs/laravel.log` for `failed` lines.

### Queue + cron on VPS (do this even though mail is sync)

Mail is sync today. You still need cron and a worker for **jobs**: data export, tenant webhooks, churn, imported-contact verify.

- [ ] `.env`: `QUEUE_CONNECTION=database` (never `sync` in production)
- [ ] Confirm `jobs` + `failed_jobs` tables exist (`php artisan migrate`)
- [ ] Crontab **every minute** (VPS allows `*`; shared hosting does not):

```cron
* * * * * cd /var/www/ledrix && php artisan schedule:run >> /var/www/ledrix/storage/logs/scheduler.log 2>&1
```

- [ ] Supervisor always-on worker (see §3 below) — **required on VPS**, not optional once volume grows
- [ ] After every deploy: `php artisan queue:restart` (already in `scripts/post-deploy.sh`)
- [ ] Weekly prune is already scheduled: `queue:prune-failed`

### Prove mail works (15 minutes)

- [ ] `php artisan tinker` → send a test `Mail::raw` to your inbox
- [ ] Register a test tenant → verify-email arrives
- [ ] Super Admin reply on a support ticket → tenant inbox
- [ ] Set a client portal password → client inbox (flash says “emailed to …”)
- [ ] Generate a payment link → client inbox
- [ ] `tail -n 20 storage/logs/scheduler.log` after one minute
- [ ] `php artisan queue:failed` is empty (or you understand each row)

---

## 2. VPS best practices

### Server

- [ ] Ubuntu 22.04+ LTS, PHP 8.2-FPM, nginx (or Apache), MySQL 8, Composer
- [ ] Document root = `public/` only — never the repo root
- [ ] `storage/` and `bootstrap/cache/` owned by the PHP user (`www-data`), mode `775`
- [ ] Swap + unattended security updates
- [ ] UFW: 22 (SSH key only), 80, 443 — no MySQL on a public interface
- [ ] Fail2ban on SSH
- [ ] Daily MySQL dump of `ledrix_primary` + `ledrix_central` off-box

### DNS / TLS

- [ ] `ledrix.co` + `www.ledrix.co` A/AAAA → this VPS
- [ ] `*.ledrix.co` wildcard → same VPS (workspace URLs)
- [ ] Let’s Encrypt **wildcard** or Cloudflare proxy for `ledrix.co` + `*.ledrix.co`
- [ ] Custom tenant domains (BYOD): Cloudflare or certbot per host — app verify alone is not HTTPS

### `.env` (VPS-specific)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ledrix.co
SEO_SITE_URL=https://ledrix.co

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=redis          # after Redis is installed; else database/file

MAIL_VERIFY_PEER=true
BILLING_ADMIN_EMAIL=ops@ledrix.co

# When you are ready for dedicated tenant DBs:
# TENANT_DB_ISOLATION=true
# TENANT_DB_PROVISION_ON_REGISTER=true
```

- [ ] `APP_KEY` generated once; never reuse a shared-host key in git
- [ ] Live Stripe + Meezan keys only on this server
- [ ] Stripe platform webhook URL updated to the VPS `APP_URL`

### Supervisor worker

```ini
; /etc/supervisor/conf.d/ledrix-worker.conf
[program:ledrix-worker]
command=php /var/www/ledrix/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/ledrix/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status
```

Cron stays for `schedule:run` (trials, renewals, tickets, storage alerts).  
Supervisor stays for the `jobs` table. Do not replace cron with Supervisor.

### Deploy

- [ ] Always `bash scripts/post-deploy.sh` (down → migrate both DBs → cache → `queue:restart` → up)
- [ ] Never run as root in the app directory after the first setup
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `APP_DEBUG=false` before you open the firewall to the world

### Watch

| Check | Command / place |
|-------|-----------------|
| Scheduler heartbeat | `storage/logs/scheduler.log` |
| App / mail errors | `storage/logs/laravel.log` |
| Worker | `storage/logs/worker.log` + `sudo supervisorctl status` |
| Stuck / failed jobs | `php artisan queue:failed` |
| Disk | `df -h` — storage alerts also email the tenant |

### Later (not required on day one)

- Redis for cache + `QUEUE_CONNECTION=redis` (faster than the `jobs` table)
- Horizon only if you switch to Redis queues and want a UI
- Re-queue *non-urgent* notifications (lead follow-up, churn) while keeping pay-link / portal-access / invoice **sync** via `SafeMail`
- Off-site log drain (Papertrail / Grafana Cloud)

---

## 3. Do not do this on VPS

- Leave `MAIL_MAILER=log` or `array` — nothing reaches inboxes
- Leave `QUEUE_CONNECTION=sync` — exports and webhooks run inside the HTTP request
- Keep Namecheap’s `*/5` cron — use `* * * * *`
- Keep `MAIL_VERIFY_PEER=false` once you are on a real ESP
- Send from `root@` or a Gmail personal account
- Skip SPF/DKIM and then debug “emails not working”
- Run `queue:work` in an SSH session and disconnect (use Supervisor)
- Point Stripe webhooks at the old shared-host URL
