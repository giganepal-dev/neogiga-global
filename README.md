# NeoGiga — Global Engineering Ecosystem

Marketplace + knowledge + learning + AI for engineers, per **Blueprint EAS-NG-001**
(NeoGiga-Enterprise-Architecture-Blueprint.md). Regional editions:
**neogiga.com** (global) · **neogiga.in** (India) · **giganepal.com** (Nepal).

## Repository layout

| Path | What it is |
|---|---|
| `giga-nepal-backend/` | **The application** — Laravel 11 modular monolith (API + SSR landing page) |
| `app/` (root) | ⚠️ Orphaned earlier model tree — richer in places, **not autoloaded**; scheduled for merge in Phase 1 (see PHASE_IMPLEMENTATION_PLAN.md). Do not add code here |
| `*_GAP_REPORT.md`, `CURRENT_CODEBASE_AUDIT.md`, `PHASE_IMPLEMENTATION_PLAN.md` | Phase-A audit set (2026-07-06) |
| `IMPLEMENTATION_SUMMARY.md`, `NEXT_PHASE_BACKLOG.md`, `VALIDATION_REPORT.md` | Phase-B/C deliverables |

## Quick start

```bash
cd giga-nepal-backend
composer install
cp .env.example .env          # then set DB_* (PostgreSQL) or sqlite
php artisan key:generate
php artisan migrate --seed    # loads marketplace schema + taxonomy/marketplace seeds
php artisan serve             # http://localhost:8000
```

- Landing page: `GET /` · Sitemap: `GET /sitemap.xml` · Health: `GET /up`
- API (v1, JSON): `GET /api/v1/marketplaces`, `/api/v1/categories/tree`,
  `/api/v1/products`, `/api/v1/products/search?q=esp32`, `/api/v1/brands`, …
- Commerce endpoints (cart/checkout/orders/POS/AI) return structured **501**
  until their phase ships — see PHASE_IMPLEMENTATION_PLAN.md.
- Admin endpoints require the `X-Admin-Token` header (`ADMIN_API_TOKEN` env; fail-closed).

## Status

Phase 0-R (repair + foundation) complete: schema loading fixed, catalog read API live,
security/SEO foundations in place, category taxonomy seeded, AI tool layer stubbed
(DB-backed, never invents price/stock). Next: Phase 1 — schema reconciliation, Sanctum
auth + RBAC, Nepal commerce core. Full backlog: NEXT_PHASE_BACKLOG.md.

© Giga Ventures Pvt. Ltd. · @NeoGigaGlobal
