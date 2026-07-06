# NeoGiga — Database Gap Report

**Baseline:** Blueprint §10 + "Database Design" DDL section · **Date:** 2026-07-06

## Blocking defects

| ID | Finding | Detail |
|---|---|---|
| DB-01 | **Marketplace migrations never load** | All 91 files live in `database/migrations/marketplace/`; Laravel only auto-loads `database/migrations/`. `AppServiceProvider` has no `loadMigrationsFrom()`. `php artisan migrate` creates only users/cache/jobs + legacy IoT tables. **Fixed this phase** (registered in `AppServiceProvider`). |
| DB-02 | **34 empty-shell migrations** | Every AI, POS, LMS, product-extras (documents/videos/compat/BOM/related/approval/lms-links/seo-meta), and import/export migration contains only `id` + `timestamps`. The corresponding models declare fillables for columns that will not exist → runtime SQL errors on first insert. Full list in CURRENT_CODEBASE_AUDIT §6. |
| DB-03 | **No `.env` / DB config** | Engine undecided. Blueprint mandates PostgreSQL 16; `.env.example` added this phase defaults to PostgreSQL with SQLite fallback documented for local dev. |
| DB-04 | **Model↔migration drift** | e.g. `Marketplace\Product` fillable has `sku`,`vendor_sku`,`regional_sku`,`product_type`; products migration has `sku`,`mpn`,`type`. Cart model in backend lives at `App\Models\Marketplace\Cart` while services import `App\Models\Cart`. Orphaned root tree diverges further. Needs one reconciliation pass (backlog, Phase 1). |

## Blueprint-convention gaps (design debt, not blockers)

| Blueprint §10 convention | Current migrations |
|---|---|
| UUIDv7 PKs | `$table->id()` bigint autoincrement |
| `created_by`/`updated_by` | Absent |
| Soft delete `deleted_at` | Absent on most tables |
| Optimistic locking `row_version` | Absent |
| Audit trigger → partitioned `audit_log` | Absent (legacy `audit_logs` table exists, unwired) |
| Partitioning (orders, price_history, ai_messages) | Absent — fine at current scale; revisit >10M rows |
| `pgvector`, `pg_trgm`, `ltree` | Absent — needed for AI RAG + MPN fuzzy + category paths later |
| `mpn_normalized` generated column + trigram index | Absent (products has plain `mpn` + index) |

**Recommendation:** don't retrofit UUIDv7/audit columns onto the current dev schema piecemeal; adopt the blueprint migration template when the Phase-1 schema reconciliation happens, before any production data exists.

## Schema coverage vs. blueprint DDL

| Blueprint domain | Tables designed | Loaded/real |
|---|---|---|
| Reference (countries, currencies) | ✅ | real (differs from blueprint: no `tax_regime`/`invoice_rules` jsonb) |
| Manufacturers + aliases + brands | ❌ manufacturers/aliases missing | `product_brands` only |
| Categories + spec_schemas | 🟠 | `product_categories` real (has `seo_meta`, `marketplace_visibility` ✅); no ltree path, no spec_schemas (uses spec_groups/specs per product instead of per category) |
| Product master (GPID, versions, specs EAV, media, assets) | 🟠 | products/variants/specs/images real; assets/versions/lifecycle absent |
| Regional SKU/price/inventory | 🟠 | prices, visibility, stocks, reserved real; no generated `available` column, no oversell guard constraint |
| Orders/invoices/payments/shipments/returns | ✅ designed | real columns; no state-machine constraints |
| Sellers/marketplace | 🟠 | vendors suite real; seller_offers-on-GPID missing |
| B2B (companies, POs, credit) | ❌ | absent |
| LMS | ❌ | empty shells |
| Community | ❌ | absent |
| AI platform (sessions, messages, tool audit) | ❌ | empty shells |
| Import/export | ❌ | empty shells |

## Seeders

- `DatabaseSeeder` calls Marketplace/Product/Vendor seeders — present and namespaced correctly. Marketplace seeder covers 10 countries, currencies, and the three launch domains.
- **Gap:** no category taxonomy seed (blueprint's 27-branch engineering taxonomy). **Added this phase** (`CategoryTaxonomySeeder`, idempotent `firstOrCreate` by slug).

## Actions taken this phase

1. Registered `database/migrations/marketplace` in `AppServiceProvider::boot()` (DB-01).
2. Added `.env.example` with PostgreSQL defaults + SQLite dev fallback (DB-03).
3. Added `CategoryTaxonomySeeder` with 27 root categories + key subcategories, SEO fields, visibility flags.
4. Documented DB-02/DB-04 as the first Phase-1 backlog items (schema reconciliation must precede any commerce handler work).
