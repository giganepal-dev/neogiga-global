# NeoGiga — Phase Implementation Plan

**Derived from:** Blueprint EAS-NG-001 §Roadmap (Phases 0–5) mapped onto the actual state of this repo.
**Date:** 2026-07-06

## Ground rules (per program owner)

- Never delete existing work; consolidate and fix in place.
- Audit → plan → implement; production-ready, secure, SEO-optimized, scalable.
- Preserve branding: deep navy / dark blue / electric cyan / gold accent / white-light gray; @NeoGigaGlobal.
- Foundation only now — no payments, no full marketplace, no paid AI API calls.

---

## Phase 0-R "Repair & Foundation" ← **THIS PHASE (implemented now)**

| # | Work item | Status |
|---|---|---|
| 0.1 | Seven audit/gap reports (this document set) | ✅ |
| 0.2 | Fix model-namespace imports breaking every service + the one live controller | ✅ |
| 0.3 | Register `database/migrations/marketplace` so schema actually loads | ✅ |
| 0.4 | `.env.example` + env docs; app boots with SQLite or PostgreSQL | ✅ |
| 0.5 | API v1 hygiene: implemented read endpoints (marketplaces, categories, brands, products, LMS placeholders), validated vendor registration; structured `501` for unimplemented commerce endpoints instead of fatals; fixed `products/search` route-order bug | ✅ |
| 0.6 | Security foundation: security-headers middleware, throttle on all API routes, admin routes behind token middleware, `APP_DEBUG=false` default | ✅ |
| 0.7 | SEO foundation: landing page (SSR Blade, full meta/JSON-LD/hreflang), robots.txt, llms.txt, dynamic sitemap.xml, `config/seo.php` | ✅ |
| 0.8 | Category taxonomy seed: 27 engineering root categories + subcategories with SEO fields | ✅ |
| 0.9 | AI commerce foundation: `AiToolsContract` + DB-backed implementation of searchProducts / getProductDetails / getRegionalInventory / createProjectBOM / findLMSLessons / createCart / createQuote / createPaymentLink / handoffToHuman (stubs where schema is missing; price/stock only ever read from DB) | ✅ |
| 0.10 | Docs: README, IMPLEMENTATION_SUMMARY, NEXT_PHASE_BACKLOG, DEPLOYMENT_NOTES, ENV_EXAMPLE, CHANGELOG, VALIDATION_REPORT | ✅ |

**Exit criteria:** `composer install` clean; `php artisan migrate --seed` succeeds; `route:list` has zero dangling handlers; landing page serves with Lighthouse-ready markup; all reports written. Results: VALIDATION_REPORT.md.

## Phase 1 — Schema reconciliation + Nepal commerce core (next)

1. **Schema reconciliation (blocker for everything):** fill the 34 empty-shell migrations (AI, POS, LMS, product extras, import/export) from the orphaned root-tree models; reconcile model↔migration drift; merge orphaned `app/` tree into `giga-nepal-backend/app/` (namespaced per bounded context) then retire the orphan tree.
2. Adopt blueprint migration template (UUIDv7, soft deletes, row_version, audit trigger) **before first production data**.
3. Real auth: Laravel Sanctum, roles (`customer`, `vendor_admin`, `regional_ops`, `catalog_ops`, `global_admin`), policies on every `{model}` binding (kills IDOR class).
4. Cart/checkout/orders for Nepal marketplace: server-side pricing only, oversell guard (`available >= qty` conditional update), order status history, invoice generation (NP VAT 13%).
5. Payment adapter interface + eSewa/Khalti/FonePay sandbox + COD; payment state machine per Blueprint §23.
6. Inventory reserve/release with TTL soft-reserve.
7. Import/export as queued jobs with dry-run diff, per-row error report.
8. Feature tests for every endpoint; CI (GitHub Actions: composer audit, pint, phpunit).

## Phase 2 — Knowledge + AI beta

- Manufacturer master + MPN normalization (+ trigram), GPID assignment, spec-schema-per-category (parametric).
- OpenSearch (or Meilisearch interim) + indexer; Redis cache layer.
- LMS v1 real schema + course/project pages with `Course`/`HowTo` JSON-LD.
- AI orchestrator service: tool registry wired to `AiToolsContract`, Claude API integration (env-gated), conversation audit table, guardrails (grounding check on price/stock), human handoff queue.
- SEO engine: product/category page templates, sharded sitemaps, hreflang expansion (ne-NP, hi-IN), OG image generation.

## Phase 3 — India cell + marketplace + B2B

- neogiga.in cell config (GST/HSN, Razorpay, Delhivery), seller offers on GPID + buy-box, settlements ledger, B2B companies/approvals/credit terms, punchout later.

## Phase 4–5 — per blueprint

- LMS full, universities, remaining AI agents; BD/LK/SG/AE cells; SOC 2 / ISO 27001 tracks; Next.js storefront replaces Blade pages (SEO continuity plan required: 301 map + sitemap continuity per Blueprint §Phase 1 exit).

## Standing risks

| Risk | Mitigation |
|---|---|
| Two model trees drift further if the orphan tree isn't merged soon | Phase-1 item #1 is scheduled first; freeze edits to root `app/` |
| Blueprint targets Next.js/NestJS; investment in Laravel deepens | Keep controllers thin over services; contracts (OpenAPI) make later extraction mechanical; decision ADR recommended |
| Empty-shell tables invite code written against phantom columns | 501-stubs block those paths until reconciliation |
| No CI = regressions invisible | Phase-1 item #8 |
