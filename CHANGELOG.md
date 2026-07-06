# Changelog

All notable changes to the NeoGiga platform.

## [0.2.0] — 2026-07-06 — Phase 0-R "Repair & Foundation"

### Audit (Phase A)
- Added full audit set: CURRENT_CODEBASE_AUDIT, ARCHITECTURE/SECURITY/SEO/DATABASE/PERFORMANCE
  gap reports, PHASE_IMPLEMENTATION_PLAN.

### Fixed
- **Fatal namespace imports** in `MarketplaceController`, `MarketplaceResolverService`,
  `AiCartService`, `BomBuilderService` (`App\Models\X` → `App\Models\Marketplace\X`).
- **Marketplace migrations never loading** — registered `database/migrations/marketplace`
  in `AppServiceProvider` (91 migrations now run).
- `products/search` route shadowed by `products/{slug}`; static segments now precede catch-alls.
- `Cart` model: wrong `User` reference, missing `calculateTotal()`; added missing
  `CartItem`, `MarketplaceProductPrice`, `VendorProductPrice`, `ProductSeoMeta`,
  `ProductBomItem`, `ProductLmsLink` models so no relation can fatal.
- Resolver: cached (1h), null-safe, port-stripping host parse.
- Seeder: hardcoded `admin123` password replaced with env/random + one-time print.

### Added
- **API v1** under `/api/v1`: implemented catalog reads (marketplaces, categories incl. cached
  tree, brands, products + search/by-category/by-brand, vendors incl. validated registration,
  inventory availability). Unimplemented commerce endpoints (cart, checkout, orders, POS, AI,
  LMS, admin import/export) return structured **501** instead of fataling.
- **Security foundation:** `SecurityHeaders` middleware (CSP, XFO, nosniff, HSTS…),
  `EnsureAdminToken` fail-closed admin gate, rate limiters (60/min API, 10/min anonymous
  writes), `.env.example`.
- **SEO foundation:** NeoGiga landing page (SSR Blade, brand theme navy/cyan/gold, semantic
  HTML, meta/OG/Twitter/canonical/hreflang, JSON-LD Organization/WebSite/Breadcrumb/FAQ,
  country+language switcher placeholders), `config/seo.php`, robots.txt with AI-crawler
  policy, `llms.txt`, dynamic `/sitemap.xml`.
- **Category taxonomy seed:** 27 engineering root categories + ~130 subcategories with SEO
  meta, visibility flags, LMS topic hints (idempotent).
- **AI commerce foundation:** `AiToolsContract` + `DatabaseAiTools`
  (searchProducts, getProductDetails, getRegionalInventory, createProjectBOM, findLMSLessons,
  createCart, createQuote, createPaymentLink, handoffToHuman) — all facts DB-sourced; missing
  capabilities throw `AiToolUnavailableException` rather than fabricate. No paid AI API wired.
- Docs: README, IMPLEMENTATION_SUMMARY, NEXT_PHASE_BACKLOG, DEPLOYMENT_NOTES, ENV_EXAMPLE,
  VALIDATION_REPORT; `composer.lock` committed.

### Known issues (tracked in NEXT_PHASE_BACKLOG.md)
- 34 empty-shell migrations (AI/POS/LMS/import-export) pending schema reconciliation.
- Orphaned root `app/` tree pending merge.
- No user auth yet (Sanctum in Phase 1); admin gate is interim.

## [0.1.0] — earlier — Initial scaffold
- Laravel 11 skeleton, marketplace schema design (91 migrations), marketplace/vendor/product
  models and seeders, mock AI services, legacy IoT schema preserved.
