# NeoGiga — Current Codebase Audit

**Date:** 2026-07-06 · **Auditor:** Claude (automated audit vs. Blueprint EAS-NG-001)
**Repo:** `neogiga-main` · **Application root:** `giga-nepal-backend/`

---

## 1. Framework & Version

| Item | Found |
|---|---|
| Framework | Laravel `^11.31` (skeleton `laravel/laravel`) |
| Language | PHP `^8.2` (host has PHP 8.4.20, Composer 2.8.4, Node 24.12) |
| Packages | Framework + Tinker only. **No Sanctum/Passport, no Scout, no Horizon, no first-party auth package** |
| Frontend | None. Default `welcome.blade.php` (stock Laravel page, title "Laravel"). No Next.js/React/Vite app code |
| Blueprint target | Next.js SSR + NestJS/Go microservices + PostgreSQL/Kafka/OpenSearch. Current code is a Laravel modular monolith — acceptable per Blueprint §7 ("start as modular monolith, extract along seams") but the seams must be kept clean |

## 2. Repository Layout — CRITICAL DEFECT

Two parallel `app/` trees exist:

| Tree | Files | Autoloaded? | Notes |
|---|---|---|---|
| `giga-nepal-backend/app/` | 92 | ✅ (PSR-4 root) | The live application |
| `app/` (repo root) | 107 | ❌ **orphaned** | Richer, better-namespaced models (`App\Models\Lms\LmsCourse` with fillables/relations) + `AiRecommendationService`, cached `MarketplaceResolverService` |

The orphaned root tree is *more complete* than the live one (backend `LmsCourse` etc. are empty `class X extends Model {}` stubs; root versions have full fillables/relations/casts). Work was generated twice and the better copy landed outside the autoload path. **Per "do not delete existing work" it is preserved and documented; it should be merged into the live tree in a controlled pass (see PHASE_IMPLEMENTATION_PLAN.md).**

## 3. Routing

- `routes/api.php`: 40 endpoints across marketplaces, categories, brands, products, vendors, inventory, cart, checkout, orders, AI, POS, LMS, admin import/export.
- **Before this audit's remediation, ~37 of 40 routes referenced controller methods that did not exist** (e.g. `OrderController@checkout`, `CartController@addItem`, `PosController@openSession`). Only the 3 `MarketplaceController` routes had real handlers — and those fataled on a wrong model import.
- `routes/web.php`: single `/` route → stock Laravel welcome view.
- Route ordering bug: `products/{slug}` is declared **before** `products/search` and `products/category/{slug}`, so `GET /api/products/search` resolves `{slug}='search'`. Same-class bug for `categories/{slug}` vs `categories/tree` (tree is declared before `{slug}` — OK there).
- No `auth`, no `throttle`, no middleware of any kind on any route. Admin routes carry a literal `// TODO: Add auth middleware`.

## 4. Controllers (17 total)

| Controller | State |
|---|---|
| `Api/Marketplace/MarketplaceController` | Implemented, but broken import (`use App\Models\Marketplace` — a namespace, not a class) |
| Other 16 (Product, Category, Brand, Vendor, Inventory, Cart, Order, POS, LMS, AI, Pricing, 5×Admin) | Empty resource stubs (`public function index() { // }`) |

No FormRequests, no API Resources, no validation anywhere.

## 5. Models

- **Backend live tree:** 45 top-level `App\Models\*` (AI/POS/LMS/Order/import-export) — most are **empty stubs**; plus 24 `App\Models\Marketplace\*` — these are real (fillables, casts, relations).
- **Orphaned root tree:** 13 domain folders (Ai, AiCommerce — duplicated twice, Cart, ImportExport, Inventory, Lms, Marketplace, Order, Pos, Pricing, Product, Seo, Vendor) with full model definitions.
- All inspected models use `$fillable` (mass-assignment protected). ✅

## 6. Database / Migrations

- 24 default+IoT migrations in `database/migrations/` (users, cache, jobs, plus a legacy IoT/device suite: devices, firmware, gps_logs, rfid_logs, sensor_logs… — preserved per Phase-1 notes).
- 91 marketplace migrations in `database/migrations/marketplace/` — **this subdirectory is never registered** (`AppServiceProvider` is empty, no `loadMigrationsFrom`), so `php artisan migrate` ignores the entire marketplace schema.
- Of those 91: the first batch (countries → shipping_fee_rules, 57 files) has real columns/indexes/FKs; the second batch (AI, POS, LMS, product extras, import/export — **34 files**) are empty shells: `id` + `timestamps` only.
- No SQLite/DB file present; `.env` absent, so DB never configured.

## 7. Services

| Service | Location | State |
|---|---|---|
| `MarketplaceResolverService` | backend | Works logically; wrong model import → fatal; return type `Marketplace` but can return null |
| `MarketplaceResolverService` | root (orphan) | Better version: correct namespace, `Cache::remember`, null-safe |
| `AiCartService`, `AiPosInvoiceService`, `BomBuilderService`, `LmsMatcherService` | backend | Logic present; all import non-existent top-level model classes → fatal on use; `marketplace_id` hardcoded to `1` |
| `AiRecommendationService` | root (orphan) | Not autoloadable |

## 8. Auth / Users

- `User` model + users migration = stock Laravel. No roles, no Sanctum tokens table, no guards beyond `web`. `config/auth.php` untouched.
- Legacy IoT migration set includes a `roles` table, unused by marketplace code.

## 9. SEO

- `public/robots.txt`: `Disallow:` (allow-all) only — no sitemap reference.
- No sitemap generation, no llms.txt, no meta/JSON-LD anywhere, landing page is stock Laravel. Full detail → SEO_GAP_REPORT.md.

## 10. Tests / CI / Deployment

- Tests: only the two Laravel example tests. No CI workflows, no Dockerfile, no deployment config in this repo.
- `composer.lock` absent → unpinned dependencies (supply-chain + reproducibility risk).
- `vendor/` absent → project has likely never been booted.

## 11. Docs

`NEOGIGA_FOUNDATION_AUDIT.md` and `NEOGIGA_PHASE1_PROGRESS.md` exist from a prior session; progress notes overstate completion (claims "35+ tables created" — they exist as files but never load; claims models complete — many are stubs).

## 12. Blueprint Alignment Snapshot

| Blueprint pillar | Current state |
|---|---|
| Global product master (GPID/MPN) | Partial: products table has `mpn`, no GPID, no manufacturer master, no spec-schema EAV |
| Regional overlay (SKU/price/stock per country) | Tables designed (prices, visibility) but unloaded; no code paths |
| Commerce (cart→order→payment) | Schema only; zero handler code |
| RFQ / B2B / Marketplace sellers | Vendors schema exists; RFQ/B2B absent |
| LMS | Empty-shell tables + stub models |
| Community | Absent |
| AI layer (10 agents, tool-calling, RAG) | 4 mock services (broken imports); no orchestrator, no guardrails, no audit |
| SEO engine | Absent |
| Identity (Keycloak/OIDC) | Absent |
| Events/Kafka, OpenSearch, Redis | Absent (file cache/session defaults) |

**Verdict:** early scaffold, ~5–10% of Phase 0. The data-model *thinking* is real; the executable surface is not. Remediation priorities in PHASE_IMPLEMENTATION_PLAN.md.
