# NeoGiga Codex Project Status Index

Audit date: 2026-07-07  
Audited target: `/home/neogiga/laravel/current` on `precious`  
Mode: audit only, no production files changed.

## Detected Stack

- Backend: Laravel `^11.31`, PHP `^8.2`
- Frontend assets: Vite `^6.0.11`, Tailwind `^3.4.13`, Axios
- Runtime env observed through Laravel config: `APP_ENV=production`, `APP_DEBUG=false`
- Active DB observed through Laravel config: `neogiga`
- Required production DB from audit brief: `neogiga_prod`
- Queue/cache/session observed: database/database/database
- Git: not a git repository in `/home/neogiga/laravel/current`

## Counts From Live Release

- Migrations: 127 files
- Applied migrations: 126 shown as ran in `php artisan migrate:status` output, including batches 1-11
- Models: 122
- Controllers: 51
- Routes: 388
- Services: 49
- Jobs: 11
- Seeders: 10
- Tests: 5 files
- Local report/docs found: 43 matching audit/report/NEOGIGA patterns

## Folder Structure Observed

- `app/Models`: marketplace, marketing, LMS, affiliate, ERP, promotion, POS, IoT/device models
- `app/Http/Controllers/Api`: auth, marketplace, product, vendor, inventory, cart, order, POS, LMS, AI, marketing, affiliate, pricing, promotions, RFQ
- `app/Http/Controllers/Api/Admin`: admin console, inventory, LMS, marketing, affiliate, finance, procurement, quotation, promotion
- `app/Services`: inventory, POS, marketing, LMS, AI, affiliate, ERP, promotion
- `database/migrations`: core IoT/geography, marketplace, marketing, LMS, inventory/POS, admin console, affiliate, ERP, promotion
- `resources/views/admin`: admin dashboard, marketplace/product/vendor/users, marketing, LMS, inventory, POS, settings, media, SEO
- `tests`: Phase 1 auth and checkout tests exist, but test runner is unavailable

## Key Immediate Risks

- Production database name is `neogiga`, not required `neogiga_prod`.
- `php artisan test` fails: command `test` is not defined, despite test files existing.
- `ImportExportController` routes still return 501.
- AI commerce endpoints still return 501.
- POS refund returns 501.
- Multiple marketing jobs are placeholders that only log.
- Admin API gate is `ADMIN_API_TOKEN` middleware, explicitly documented as a placeholder for Sanctum/RBAC.
- Products, vendors, orders, brands, suppliers, payments, affiliates, coupons, gift cards are effectively empty, so many modules are schema/API foundations rather than complete production workflows.

