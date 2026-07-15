# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

NeoGiga is a global electronics engineering marketplace — a **Laravel 11 modular monolith** (`giga-nepal-backend/`) serving Blade SSR landing pages + a JSON API (v1) across 26+ regional marketplaces. The root `app/` directory is **orphaned** (not autoloaded); all active code lives inside `giga-nepal-backend/`.

## Always-in-effect architecture

### Multi-marketplace resolution

Every request is scoped to a marketplace resolved from the `Host` header (`MarketplaceResolverService`). The resolved `Marketplace` model drives catalog visibility, pricing currency, locale, and SEO rendering. Global master = `neogiga.com`; regional editions include `neogiga.in` (India), `giganepal.com` (Nepal), and 24 country-prefixed subdomains (`np.neogiga.com`, `in.neogiga.com`, …). No forced geo-redirects — resolution only selects scope.

The regional configuration is in `config/neogiga_global.php` (prefixes, currencies, payment gateways, SEO templates). Canonical host overrides are env-driven to allow DNS cutover without code changes.

### Auth model (interim, pre-Sanctum)

- **API auth**: custom bearer token (`api.token` middleware) + RBAC (`permission` middleware with named permissions). Tokens are issued by `AuthController`; the `AuthenticateApiToken` middleware validates them from the `users` table.
- **Admin web**: session-based Blade auth via `admin.web` middleware (`EnsureAdminWeb`). Admin API uses `X-Admin-Token` header (`ADMIN_API_TOKEN` env, fail-closed if empty).
- **Seller/Distributor**: separate auth endpoints under `/api/v1/seller/` and `/api/v1/distributor/`, each gated by `permission:seller.access` / `permission:distributor.access`.

### Service layer

Business logic lives in `app/Services/` organized by domain: `Catalog/`, `Marketplace/`, `Pricing/`, `Bom/`, `Ai/`, `Seller/`, `Affiliate/`, `Seo/`, `Inventory/`, `POS/`, `Payments/`, `Promotion/`, `Vendor/`, `B2B/`, `Lms/`, `Erp/`, `Importers/`, `CatalogImport/`, `CustomerImport/`, `Data/`, `Product/`.

Models are at `app/Models/` with subdirectories for domain grouping: `Marketplace/` (core commerce — Product, Cart, Order, Vendor, etc.), `Bom/`, `Lms/`, `Pcb/`, `Affiliate/`, `B2B/`, `Pricing/`, `Distributor/`, `Erp/`, `Marketing/`, `Payments/`, `Promotion/`, `Supplier/`.

### Catalog import pipeline

Two major import sources: **JLCPCB** (SMT parts library → approved → published products) and **ElecForest** (supplier catalog via API). Both follow a staged pipeline:

1. Import raw data into staging tables
2. Audit/validate (brand mapping, category mapping, image validation, SEO metadata)
3. Download + process images (`public` disk, host-gated)
4. Generate regional SEO metadata (titles, descriptions, canonicals)
5. Publish qualified rows to live `products` / `marketplace_product_prices`

Artisan commands for each stage live in `app/Console/Commands/` (e.g., `ImportElecforestProducts`, `ElecforestDownloadImagesCommand`, `PublishQualifiedJlcpcbProductsCommand`). Each stage can run independently or be chained via admin UI (Blade) or queue.

### Pricing engine

Central pricing anchored on `USD` base currency (`config/pricing.php`). Exchange rates (`ExchangeRate` model) are loaded from DB (manual entry or `RefreshExchangeRates` command). Each marketplace can have a configurable margin percent (`marketplace_settings.pricing.margin_percent`). Prices are stored at the `MarketplaceProductPrice` level per-marketplace, with provenance tracking to the source import row.

### Regional SEO

Country-prefixed routes (`/en/`, `/in/`, `/np/`, …) with localized sitemaps, hreflang tags, and canonical URLs. SEO metadata is stored in `product_seo_meta` / `seo_meta` tables, generated per-region during catalog import. `SeoMeta` model ties to any entity via polymorphic relation.

## Key commands

```bash
cd giga-nepal-backend

# Development
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # PostgreSQL or SQLite
php artisan serve                  # http://localhost:8000

# Tests — require PostgreSQL (neogiga_test DB) per phpunit.xml
php artisan test                   # all tests
php artisan test --filter=SomeTest # single test
php artisan test --parallel        # parallel (if DB handles it)

# Dev environment (server + queue + logs + vite, runs concurrently)
composer run dev

# Queue workers (database driver default)
php artisan queue:work --queue=imports,catalog-imports,catalog-media,catalog-media-derivatives

# Code style
./vendor/bin/pint                  # Laravel Pint (PSR-12)

# Cache safety (see memory: neogiga-ops-safety)
php artisan config:clear           # always before isolated DB operations
```

**Test DB**: Tests use PostgreSQL (`neogiga_test` DB, user `ashokdhamala`). If you don't have PostgreSQL running, tests will fail. Use SQLite in-memory by overriding `phpunit.xml` env vars.

## Routes at a glance

| Route prefix | File | Auth |
|---|---|---|
| `GET /` (landing, sitemap, etc.) | `routes/web.php` | public |
| `GET /admin/*` | `routes/web.php` | `admin.web` session |
| `GET/POST /api/v1/*` | `routes/api.php` | public (throttled) |
| `GET/POST/PATCH /api/v1/seller/*` | `routes/api.php` | `api.token` + `permission:seller.access` |
| `GET/POST /api/v1/admin/*` | `routes/api.php` | `admin.token` + `admin.permission` |
| `artisan ...` | `routes/console.php` | CLI only |

## Key config files

- `config/neogiga_global.php` — 26 country prefixes, currencies, payment gateways, SEO templates
- `config/marketplace.php` — host-guard allow-list (off by default)
- `config/pricing.php` — base currency, rate staleness, default margin
- `config/jlcpcb_*.php` — JLCPCB import governance (commerce enrichment, qualified publication)
- `config/elecforest_import.php` — ElecForest source configuration, image constraints
- `config/catalog_release.php` — draft catalog release workflow settings
- `config/filesystems.php` — `public` disk for product media, CDN integration

## Important patterns

- **No Eloquent `$fillable` by convention** — most models use `$guarded = []` or explicit `$fillable`. Check the model before mass-assigning.
- **Env-configured feature flags**: `config('neogiga_global.features.*')` gates locale-prefix routes, geo-redirects, localized sitemaps, and localized pricing. Features are OFF by default except `locale_prefix_routes`.
- **Cache everywhere**: Marketplace resolution, category trees, exchange rates, and SEO metadata are cached. Clear with `php artisan cache:clear` after data changes.
- **Queue jobs are DB-backed** (default) with named queues: `imports`, `catalog-imports`, `catalog-media`, `catalog-media-derivatives`, `marketing`, `campaign-preparation`, `transactional`, `webhooks`.
- **Strict host-based routing**: Admin is on `admin.neogiga.com` (subdomain route in `web.php`), regional marketplaces on their respective domains. `EnsureAllowedHost` middleware guards against host spoofing (opt-in via config).
