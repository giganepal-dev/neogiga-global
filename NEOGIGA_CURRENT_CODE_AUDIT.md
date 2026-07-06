# NeoGiga — Current Code Audit

**Date:** 2026-07-07 · Project: `~/Downloads/neogiga-main 2/giga-nepal-backend`. Read-only.

## Stack
- **Backend:** Laravel **11.31** (PHP 8.4 on server; 8.5 local). API-first + SSR Blade for landing/admin.
- **Frontend:** server-rendered **Blade** (no Nuxt/Next/React SPA). Self-hosted CSS (CSP `script-src 'self'`), system fonts.
- **DB:** **PostgreSQL 16** (`neogiga`) with pgvector/pg_trgm/ltree extensions. Cache/session/queue = database driver.
- **Web:** Apache + fcgid (php8.4) under Virtualmin; split vhosts backend/admin/neogiga.

## Inventory (counts)
- Migrations: **117** · Models: **77** · Controllers: **25** · API routes: **75** · Web routes: **16**

## Structure highlights
- `app/Models/Marketplace/*` — Marketplace, Product, ProductCategory, Vendor, MarketplaceDomain, etc. (domain-namespaced).
- `app/Http/Controllers/Api/*` — catalog, marketplace, vendor, inventory, cart, order, AI, POS, LMS, admin import/export.
- `app/Http/Controllers/Admin/*` — **AuthController, DashboardController** (web admin console, built last session).
- `app/Http/Controllers/Web/*` — LandingController, SitemapController, **CategoryController** (built, undeployed).
- `app/Http/Middleware/*` — SecurityHeaders, AuthenticateApiToken (`api.token`), EnsurePermission (`permission:`), EnsureAdminToken (`admin.token`), **EnsureAdminWeb (`admin.web`)**.
- `app/Services/*` — MarketplaceResolverService, AiCartService, BomBuilderService, LmsMatcherService, AiPosInvoiceService, Ai/DatabaseAiTools.
- `resources/views/admin/*` (8 views), `resources/views/frontend/*` (layout + categories, undeployed), `landing.blade.php`.

## Auth / admin
- **API:** first-party bearer token (`users.api_token_hash`, SHA-256), `AuthController` register/login/me/logout; RBAC via `Role.permissions[]` + `permission:` middleware.
- **Admin web:** session `web` guard login at `/admin/login`, `admin.web` role gate (super_admin|admin). Live.
- Roles seeded: super_admin, admin, seller, customer, support.

## Feature presence check (grep across app/ database/ routes/)
| Feature | Present? | Evidence |
|---|---|---|
| multi-country marketplace | ✅ | marketplaces + domains + resolver |
| separate DB config | ✅ | pgsql `neogiga` |
| product catalog / categories / brands / variants / specs | ✅ | schema + 177 categories seeded |
| vendor system + regional approval | ✅ / 🟡 | vendors suite; approval schema, no UI |
| inventory / multi-warehouse | 🟡 | schema + read endpoints |
| POS | 🟡 | 42 files, 501 endpoints |
| cart / order / payment | 🟡 | controllers + checkout test; no gateway |
| LMS | 🟡 | 33 files, 501 |
| AI commerce / BOM | 🟡 | tool stubs |
| newsletter / campaign / WhatsApp / abandoned cart | ❌ | 0 files |
| email OTP login | ❌ | 0 files |
| order/transactional emails | 🟡 | mail config only |
| analytics / GA4 / trending / top searches | ❌ | 0 files |
| admin dashboard | ✅ | Blade console live |
| affiliate / referral | ❌ | 0 files |
| gift card / coupon / wallet | ❌ | 0 files (docs only) |
| import / export | 🟡 | route shell |

## IoT / device modules
- The **older** `neogiga-main` folder is IoT/device-portal-centric (device/module admin). The **current** `neogiga-main 2` retains device-era migrations (devices, firmwares, gps_logs, sensor_logs, sites, network_providers) — **preserved**, not removed. No conflict with marketplace tables.

## Build / test / env safety
- Tests: `Phase1AuthTest`, `Phase1CheckoutTest` (pass, 25 assertions) via isolated `neogiga_test` PG DB (`phpunit.xml` pinned; `.env.testing` local). `php artisan test` **not installed on prod** (`--no-dev`).
- **Git: not a repo** (empty `.git`). No version history → deploys are file-copy; recommend `git init`.
- Prod `.env`: APP_ENV=production, APP_DEBUG=false, secrets present (not committed, web-blocked).

## Notable risks (detail in gap report)
1. **Sitemap→404** for 177 category URLs (undeployed frontend pages). **P0.**
2. **No git** → no auditable history/rollback. **P0-ops.**
3. Test admin password (`NeoGiga@Admin2026`) should be rotated. **P1.**
4. Demo seeders carry column drift (DB-04) → `SEED_DEMO` unusable until reconciled. **P1.**
5. Growth/marketing/analytics/affiliate/wallet modules **documented but uncoded**. **P2–P3.**
