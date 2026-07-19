# NeoGiga Transactional Email Failure Report — 2026-07-19

Branch: `fix/transactional-email-and-product-schema` · Backup tag: `backup/pre-email-schema-fix`

## Root causes (ranked, evidence-based)

| # | Cause | Evidence | Fix |
|---|-------|----------|-----|
| 1 | **No DKIM signing** + strict DMARC | `dig TXT` on selectors `resend/default/google/k1/s1/mail` → none exist. DMARC is `p=quarantine; adkim=s; aspf=s; pct=25` (strict alignment). MX/SPF (`v=spf1 mx a:mail.neogiga.com ip4:217.216.78.56 -all`) show self-hosted SMTP from the app VPS. Gmail requires aligned DKIM/SPF since 2024 → **Gmail recipients (ecoholidayasia@gmail.com) rejected/junked**. | DNS + opendkim (runbook §A) |
| 2 | **Queue worker not running** on the migrated server | Server migrated 2026-07-17; no worker unit installed. `SendTransactionalEmailJob` rows stay `queued` forever. | systemd unit (runbook §B) |
| 3 | **Mailer env not production-ready** | `config/marketing.php`: `enabled` defaults **false**, `test_mode` defaults **true**, mailer falls back to **`log`**. If prod `.env` lacks the four vars, mail is silently written to the log file. | env flip (runbook §C) |
| 4 | Job dispatched before DB commit (race) | `EmailQueueService::queue()` dispatched without `afterCommit` | **FIXED in code** — dispatch now `->afterCommit()` |
| 5 | Order-status emails thin + non-deterministic idempotency | `OrderNotificationService::orderStatus()` sent no `event_id`, no order lines/amounts | **FIXED in code** — deterministic `event_id: status-{status}` + real order lines, total, currency, payment status |

Causes ruled out by code audit: template rendering exceptions (all 3 templates render in tests), missing registration trigger (wired at `CustomerAuthController:96` → `AccountCommunicationService::registration()`), duplicate sends (idempotency key = sha256 of event|related|event_id|recipient, enforced in `EmailQueueService`), marketing opt-out blocking account email (`EmailEligibilityService->transactional()` is a separate eligibility path from marketing).

## Per-recipient failure table

Prod DB access was unavailable from this session (SSH publickey denied). Run on the server to fill this table:

```sql
SELECT to_email, status, provider, attempts, failure_reason,
       metadata->'provider_result' AS provider_response, created_at
FROM email_messages
WHERE to_email IN ('ecoholidayasia@gmail.com','ashok@ecoinc.com.np')
ORDER BY id DESC LIMIT 20;
```

| Recipient | Event | Provider | Attempt | Failure reason | Provider response | Fix | Retest result |
|---|---|---|---|---|---|---|---|
| ecoholidayasia@gmail.com | (run SQL above) | pending prod query | – | expected: Gmail 550-5.7.26 unauthenticated | – | DKIM + worker + env | pending deploy |
| ashok@ecoinc.com.np | (run SQL above) | pending prod query | – | expected: stuck `queued` (no worker) | – | worker + env | pending deploy |

## Existing pipeline verified healthy (no changes needed)

- Retry/backoff: `SendTransactionalEmailJob` `tries=3`, backoff `[30,120,600]`, throws only on `retryable` failures (transient 429/5xx) — permanent 4xx marked failed once. ✔
- Suppression: eligibility check → `suppressed` status, logged. ✔
- Logging: `email_messages` + `email_message_events` + `communication_logs` (hashed recipient) + `communication_failures`. ✔
- Registration flow: create → login → queue registration_received + email_verification (try/catch, non-blocking). ✔
- Order approval (admin `updateOrderStatus`): status-change guard (no-op when same status), emails **after** the transaction, audit row in `order_status_histories`. ✔ ("approved" in this domain = `confirmed`.)
- Seller API `updateStatus` touches `vendor_orders` (a vendor slice, not the customer order) — customer email there would be incorrect; intentionally not wired.

## PROD RUNBOOK (operator, in order)

```bash
# §A DKIM + DNS  (self-hosted path; alternative: switch TRANSACTIONAL_MAILER to a verified provider)
apt-get install -y opendkim opendkim-tools
opendkim-genkey -D /etc/opendkim/keys/neogiga.com -d neogiga.com -s mail
# publish mail._domainkey.neogiga.com TXT from mail.txt, wire opendkim into postfix, then:
opendkim-testkey -d neogiga.com -s mail -vvv
# relax DMARC alignment while ramping: adkim=r; aspf=r  (keep p=quarantine)

# §B queue worker (systemd)
cat >/etc/systemd/system/neogiga-worker.service <<'EOF'
[Unit]
Description=NeoGiga queue worker
After=network.target postgresql.service redis.service
[Service]
User=neogiga
Restart=always
RestartSec=5
WorkingDirectory=/home/neogiga/neogiga-global/giga-nepal-backend
ExecStart=/usr/bin/php artisan queue:work --queue=transactional,imports,catalog-imports,catalog-media,catalog-media-derivatives,marketing,webhooks --sleep=3 --tries=3 --max-time=3600
[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload && systemctl enable --now neogiga-worker

# §C env (names only — set values in prod .env; never commit)
# TRANSACTIONAL_EMAIL_ENABLED=true
# TRANSACTIONAL_EMAIL_TEST_MODE=false
# TRANSACTIONAL_MAILER=smtp        # or resend/ses once domain-verified at the provider
# MAIL_FROM_ADDRESS=no-reply@neogiga.com
php artisan config:clear && php artisan config:cache && systemctl restart neogiga-worker

# §D controlled test
php artisan tinker --execute="app(\App\Services\Marketing\TransactionalEmailService::class)->queue('YOUR_TEST_ADDRESS','NeoGiga delivery test','<p>Test '.now().'</p>');"
# then: SELECT id,status,provider,provider_message_id,failure_reason FROM email_messages ORDER BY id DESC LIMIT 3;
```

## Rollback

```bash
git revert <commit-sha>                                # revert the single commit, or:
git checkout backup/pre-email-schema-fix -- giga-nepal-backend   # restore exact pre-fix tree
systemctl disable --now neogiga-worker                 # undo worker (if needed)
```
