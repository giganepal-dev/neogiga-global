# NeoGiga — Live Deployment Report

**Date:** 2026-07-06 · **Host:** host.giganepal.com (217.217.249.72) · **Engineer:** automated deploy (senior DevOps role)

> **Key decision (confirmed with owner):** The deployment spec specified **MySQL `neogiga_prod`** and a **`/var/www/neogiga` + Nginx/www-data** layout. The live NeoGiga already runs on **PostgreSQL** under **Virtualmin/Apache** (built earlier per the Blueprint "System of Record" mandate). Owner chose to **keep PostgreSQL** and **keep the Virtualmin/Apache layout**. The spec's *goals* (three domains, www redirect, isolation, hardening, admin, reports) were therefore adapted onto the working system. **No MySQL `neogiga_prod` was created; no `/var/www/neogiga` tree was built; no other project was touched.**

## 1. Server audit summary
Full detail in `DEPLOYMENT_SERVER_AUDIT.md`. Highlights: Ubuntu 24.04.4, PHP 8.4.23 (Apache fcgid), Apache 2.4.58 under Virtualmin, web user `neogiga` (uid 1010), Composer 2.9.8, Node 22 / npm 10 / pm2 7. Shared server also hosts giganepal, precious_nepal, device portals, and a Node app on :3001. Both MySQL 8.0.46 and PostgreSQL 16.14 present.

## 2. Deployed code version
Deployed as files (no active git repo — local `.git` is empty). Live release: `/home/neogiga/laravel/releases/20260706-140308` (symlinked `current`). Changes this deploy: `config/cors.php` (new), `.env` (3 keys added), Apache vhosts. Local source-of-truth updated to match.

## 3. Backups (Phase 2 + Phase 11)
Directory: `/home/neogiga/deploy-backups/20260706-165554/`
- `backend.env.bak` — live `.env` (copy; original never overwritten)
- `neogiga.com.conf`, `backend.neogiga.com.conf` — pre-change Apache vhosts
- `neogiga_pg_2026-07-06.dump` — **pre-change** PostgreSQL dump (1.22 MB, verified)
- `neogiga_prod_after_deploy_2026-07-06_17-07-00.dump` — **post-deploy** PostgreSQL dump (1.22 MB, verified)

## 4. Database (isolation)
- Engine: **PostgreSQL 16.14** (localhost:5432)
- DB name: **`neogiga`** · DB role: **`neogiga`** (owns only the `neogiga` DB; no global privileges; not a superuser; not `postgres`)
- Password: stored only in live `.env` (`DB_PASSWORD`), generated random 48-hex
- Other databases (MySQL `giganepal`, `precious_nepal`, `giga_device_portal`, `giganepa_wt858`, `neogiga_app`; PostgreSQL `postgres`) — **verified untouched**
- `neogiga_prod` (MySQL) — **not created** (PostgreSQL kept per decision)

## 5. Domains configured
| Domain | Behaviour | Verified |
|---|---|---|
| neogiga.com | Public marketplace (Laravel SSR landing), HTTPS | 200 |
| www.neogiga.com | **301 -> https://neogiga.com** (HTTP + HTTPS) | 301 |
| backend.neogiga.com | Laravel 11 API (`/api/v1/*`), HTTPS | 200 |
| admin.neogiga.com | Laravel app, HTTPS, **X-Robots-Tag noindex**, admin API auth-gated (401 w/o token) | 200 / 401 |
| neogiga.com/api/* | 302 -> backend.neogiga.com | 302 |

## 6. SSL status
Let's Encrypt, auto-renew via existing certbot/Virtualmin timer.
- `neogiga.com` cert — SANs cover **neogiga.com, www.neogiga.com, admin.neogiga.com**, mail — valid to **2026-10-04**
- `backend.neogiga.com` cert — valid to **2026-10-04**
- No new certs needed; admin/www reuse the neogiga.com SAN cert.

## 7. Web-server config changed
- `/etc/apache2/sites-available/neogiga.com.conf` — added www->non-www 301 (HTTP+HTTPS); removed `admin` ServerAlias + admin->Virtualmin-panel redirect. (Backed up.)
- `/etc/apache2/sites-available/admin.neogiga.com.conf` — **new** vhost: serves `/home/neogiga/laravel/current/public`, HTTPS (neogiga.com cert), `X-Robots-Tag: noindex, nofollow, noarchive`, fcgid PHP 8.4. `a2ensite` enabled.
- `mod_headers` confirmed enabled. `apache2ctl configtest` = Syntax OK before every reload; reloads gated on it (rollback path tested).
- App-level: `config/cors.php` restricts CORS to the 3 origins; `SecurityHeaders` middleware already emits X-Frame-Options/X-Content-Type-Options/Referrer-Policy/Permissions-Policy/CSP/HSTS globally.

## 8. .env changes (additive only — no existing key overwritten)
Added: `FRONTEND_URL=https://neogiga.com`, `ADMIN_URL=https://admin.neogiga.com`, `SESSION_SAME_SITE=lax`. Confirmed already correct: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `ADMIN_API_TOKEN` set, DB->PostgreSQL `neogiga`.

## 9. Laravel commands run
`config:cache`, `route:cache`/`view:cache` (already cached), CORS deploy + `config:cache`. **No destructive commands** (`migrate:fresh`/`reset`/`wipe`/`truncate`) run against production this session.

## 10. Migration & seed status
- `migrate:status`: **117 migrations ran, 0 pending** (PostgreSQL).
- Seed (reference data, idempotent): countries 10, currencies 10, marketplaces 3, marketplace_domains 3, product_categories 177, ai_project_templates 10, roles 5, admin user 1. Demo product/vendor seeders gated behind `SEED_DEMO=true` (schema drift, DB-04).

## 11. Services configured (Phase 10)
- **Scheduler cron** installed for user `neogiga`: `* * * * * cd /home/neogiga/laravel/current && php artisan schedule:run >> /dev/null 2>&1`.
- **Queue worker: intentionally deferred** — NeoGiga currently defines **0 job classes and 0 scheduled tasks**, so a persistent worker would idle for nothing (spec rule 10). Add a `neogiga-queue.service` systemd unit (mirroring `giga-device-queue.service`, but `User=neogiga`) once Phase-1 imports/notifications dispatch jobs.

## 12. Verification results (Phase 12)
- APP: env=production, debug=false, DB=neogiga (pgsql) ✓
- All 8 public endpoints 200; www 301; admin 200+noindex; admin API 401 without token ✓
- CORS: allowed origin echoed, disallowed origin denied ✓
- Sensitive files: `.env` 403, `.git/config` 403, `composer.json` 403, `storage/logs` 403, `vendor` 404 ✓
- SSL valid on all four hostnames ✓
- 58 `api/v1` routes; `route:list` clean ✓
- Log: **0 new errors** since deploy (latest error 14:35, pre-migration; deploy at ~17:00) ✓
- Other project DBs unmodified ✓

## 13. Errors found & fixed during deploy
- vhost edit via SSH/perl mangled the www RewriteCond (`^www.neogiga.com0NC]`) — caught by pre-apply diff; rebuilt the config file locally and pushed instead (clean). No bad config ever reached Apache.

## 14. Remaining risks / notes
1. **Virtualmin may regenerate `neogiga.com.conf`** (if the domain is edited in the Virtualmin UI), which would re-add the `admin` alias and drop the www redirect. Re-apply from backup, or set these via Virtualmin's "Directives and settings" so they persist. `admin.neogiga.com.conf` is standalone and won't be regenerated.
2. **admin.neogiga.com serves the same Laravel app** (no distinct admin UI exists yet). It is HTTPS + noindex and its admin API is auth-gated, but the root currently shows the public landing. Build/point a real admin dashboard here in a later phase.
3. **Queue worker not running** (deferred — no jobs yet).
4. **Local repo is not under git** (empty `.git`); recommend `git init` + commit so deploys diff cleanly.
5. Historical `production.ERROR` lines in the log are from the earlier MySQL->PostgreSQL migration work, not this deploy.

## 15. DNS records (already live — for reference)
| Host | Type | Value |
|---|---|---|
| neogiga.com | A | 217.217.249.72 |
| www.neogiga.com | A/CNAME | 217.217.249.72 (or CNAME neogiga.com) |
| backend.neogiga.com | A | 217.217.249.72 |
| admin.neogiga.com | A | 217.217.249.72 |

All four already resolve to the server; no DNS change required.

## 16. Next recommended steps
1. Reflect the vhost changes in Virtualmin's per-domain directives so they survive regeneration.
2. Build the real admin dashboard (SPA or Laravel admin) and point admin.neogiga.com's docroot/routes at it; enforce admin-role auth (replace static `admin.token` with RBAC).
3. Phase-1 schema reconciliation (DB-02/DB-04) -> unlock `SEED_DEMO` catalog; then wire the queue worker.
4. Add CI (composer audit, Pint, PHPUnit against `neogiga_test`, Gitleaks) — the isolated test DB + phpunit.xml are already prepared.
5. `git init` the repository for auditable, diff-based deploys.
