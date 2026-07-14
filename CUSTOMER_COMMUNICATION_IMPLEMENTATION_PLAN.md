# Customer Communication Implementation Plan

Generated: 2026-07-13
Implementation mode: additive upgrade; existing customers, orders, templates, campaigns, routes, and UI retained

## Delivered Architecture

The implementation extends the existing Laravel marketplace and marketing modules instead of replacing them:

- **Import layer:** XLSX/XLS/CSV/ODS reader, configurable header aliases, worksheet preview, validation, country normalization, idempotent invoice/contact deduplication, resumable queue jobs, dry-run CLI, and row-level provenance.
- **Customer identity layer:** separate customer accounts/companies, contacts, emails, phones, addresses, invoice references, sources, marketplace association, consent, preferences, subscriptions, lists, and suppressions.
- **Administration layer:** import wizard/history/errors, CRM tabs, country summaries, segments, consent/suppression/merge/communication views, masked previews, authorized formula-safe CSV export, and permission-aware navigation.
- **Marketing layer:** provider contract, sandbox, encrypted admin-configured SMTP, and configurable generic HTTP adapter; immutable campaign/newsletter template versions, variable/content checks, approval gates, frozen consent-aware audience snapshots, test allowlist, scheduled preparation, resumable rate-limited batches, webhook linkage, and campaign/newsletter analytics.
- **Transactional layer:** independently configured transport/queue, retries/backoff, regional sender gates, idempotent communication records, and wired services for account verification/welcome/password changes, OTP, order, payment, shipment, invoice, RFQ/BOM, seller/distributor onboarding, support, and security event templates.
- **Governance layer:** opaque preference tokens, confirmation-based unsubscribe, central eligibility/suppression service, signed idempotent provider webhooks, encrypted raw webhook bodies, audit logging, regional branding, disabled-by-default senders, and deliverability checklist.

## Phase Status

| Phase | Result |
| --- | --- |
| 1 — audit and design | Complete: four audits plus normalized additive schema and security/consent model |
| 2 — importer | Complete: reference workbook mapping, validation, preview, async/resume, reports, CLI |
| 3 — CRM and segments | Complete: customer/company/contact/country views, filters, segments, preferences, export controls |
| 4 — transactional channel | Complete at application layer: separate config/queue, logs, regional sender guards, order/OTP wiring |
| 5 — marketing campaigns | Complete at application layer: abstraction, approval/test gates, snapshots, scheduler, batch queue |
| 6 — webhooks and analytics | Complete: signature verification, deduplication, suppression updates, event/country analytics |
| 7 — regional/deliverability/tests/docs | Complete locally, including encrypted admin provider setup; credential issuance and external DNS verification remain operational tasks |

## Import Operation

Preview without writes:

```bash
php artisan neogiga:import-customers '/path/Customer Invoice Details (8).xlsx' \
  --profile='Customer Invoice Details' \
  --dry-run \
  --no-marketing-consent
```

Queue a reviewed import:

```bash
php artisan neogiga:import-customers '/path/file.xlsx' \
  --profile='Customer Invoice Details' \
  --source='Reviewed customer invoice export' \
  --marketplace='GLOBAL' \
  --only-valid \
  --no-marketing-consent \
  --queue
```

Every persisted imported row stores source name/URL/file/page, download/import times, data year, license note, confidence, raw source values, and normalized values. Imports are transactional and idempotent. Existing values are filled only when `--update-existing` permits it; no import grants promotional consent automatically.

## Production Deployment Runbook

1. Put the application in the normal reviewed release workflow and take database plus `.env`/secret-manager backups.
2. Deploy code and run `composer install --no-dev --prefer-dist --optimize-autoloader` using the committed lock file.
3. Run `php artisan migrate --force`; all customer communication migrations are additive and reversible.
4. Run `php artisan db:seed --class=RoleSeeder --force` and `php artisan db:seed --class=EmailCommunicationSeeder --force`. Both seeders are idempotent and sender profiles remain disabled/unverified.
5. Run `php artisan config:cache`, `php artisan route:cache` where compatible, and `php artisan view:cache` as the final cache steps.
6. Run the scheduler every minute and separate workers so promotional load cannot delay service mail:

```bash
php artisan schedule:run
php artisan queue:work --queue=transactional --tries=3
php artisan queue:work --queue=webhooks --tries=5
php artisan queue:work --queue=imports --tries=3 --timeout=600
php artisan queue:work --queue=campaign-preparation --tries=3
php artisan queue:work --queue=marketing --tries=5
```

7. Configure SMTP/API/webhook secrets through the permission-gated Communication Settings panel, which stores them encrypted with `APP_KEY`, or use the environment/secret-store fallback. Never commit credentials.
8. Complete SPF/DKIM/DMARC and provider verification from `EMAIL_DELIVERABILITY_PLAN.md`.
9. Keep both channels in test mode, verify internal test recipients, regional URLs, unsubscribe/preferences, bounce processing, and queue priority.
10. Enable transactional delivery first. Enable marketing only after an authorized approver reviews audience, consent, suppressions, sender verification, and daily/rate limits.

## Required Environment Controls

The supported environment fallback variables are documented in `giga-nepal-backend/.env.example`. Safe defaults are `MARKETING_EMAIL_PROVIDER=sandbox`, `MARKETING_EMAIL_TEST_MODE=true`, `MARKETING_EMAIL_SENDING_ENABLED=false`, `TRANSACTIONAL_EMAIL_ENABLED=false`, and `TRANSACTIONAL_EMAIL_TEST_MODE=true`. Once an authorized administrator saves a channel in Communication Settings, its encrypted database configuration becomes authoritative; restart long-running queue workers after transport changes.

Admin APIs fail closed without `ADMIN_API_TOKEN`; granular permissions can be listed in `ADMIN_API_TOKEN_PERMISSIONS`. Web and API campaign/import/export/settings actions also enforce named permissions.

## Verification and Acceptance

Local verification completed against Laravel 12.63/PHP 8.5:

- supplied workbook dry-run: one worksheet row, one valid row, zero errors, zero writes, consent `unknown`;
- idempotent real-import fixture: NORATEL company/contact/email/phone/Sri Lanka/address/invoice linkage with provenance and no marketing opt-in;
- full backend suite: 149 passed, 11 deliberately skipped duplicate-module tests, 598 assertions;
- focused importer/communication/account/checkout suite: 26 passed, 175 assertions;
- Composer validation and security audit: clean, no known advisories;
- all Blade/config caches compile; 900 routes load; scheduler lists campaign preparation every minute;
- Vite production build succeeds.

No real customer message was sent during implementation or verification.

## Rollback and Data Safety

- Pre-migration SQLite and pre-framework-upgrade Composer backups are stored below `.codex-backups/` with checksums.
- Disable channel flags and pause campaigns before rolling application code back.
- Roll back only the newest migration batch after taking a fresh database snapshot and confirming no production communication records depend on it.
- Never delete imported source rows, consent evidence, suppression history, audience snapshots, webhook events, or communication logs to roll back delivery.
- Restore Composer manifests from `.codex-backups/20260714-083002-pre-laravel-security-upgrade/` only if an application rollback also returns to a reviewed, security-supported framework line.

## External Tasks That Cannot Be Automated Safely

- obtain provider/SMTP credentials from the selected vendor and authorize their production use;
- publish DNS records and verify domain ownership;
- approve country-specific legal/retention policy;
- authorize the first live campaign and its recipient audience;
- monitor initial reputation/ramp metrics.

These are explicit activation gates, not incomplete code paths.
