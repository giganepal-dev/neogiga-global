# NeoGiga — Performance Gap Report

**Baseline:** Blueprint §41 (Performance) + §5 NFRs (p95 <200ms cacheable reads) · **Date:** 2026-07-06

## Context

The app has never booted; there is no runtime profile to measure. This report therefore covers *structural* performance readiness.

## Findings

| ID | Finding | Severity | Notes / action |
|---|---|---|---|
| PERF-01 | Cache/session/queue drivers default to `database`/`file` — no Redis | Medium | Fine for dev. `.env.example` documents `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` for production (Blueprint cache hierarchy L3) |
| PERF-02 | Backend `MarketplaceResolverService` queries DB on every request (per-instance memo only) | Medium | Orphaned root version already had `Cache::remember` 1h + domain-keyed invalidation — that pattern restored in the fixed service this phase |
| PERF-03 | No pagination anywhere (`Marketplace::get()`, and stub controllers would default to `all()`) | High once catalog grows | All list endpoints implemented this phase use `paginate()` with capped `per_page` |
| PERF-04 | N+1 risk: models define many relations; no eager-loading conventions | Medium | Implemented endpoints use explicit `with([...])`; convention documented |
| PERF-05 | No HTTP cache headers on API or pages | Medium | Landing page + catalog reads get `Cache-Control: public, max-age` this phase; event-driven purge is Phase 2 (needs CDN) |
| PERF-06 | Search = SQL `LIKE` at best; no OpenSearch, no trigram index on `mpn` | High at scale | Acceptable for foundation; `products.slug/sku/mpn` indexes exist. OpenSearch is a Phase 2 dependency (Blueprint §11) |
| PERF-07 | No queue workers/async path; heavy ops (imports, image processing) would run in-request | High later | Import/export handlers deferred; must be queued jobs when implemented |
| PERF-08 | Landing page: stock welcome shipped ~40KB inline CSS + external font + remote hero image | Low | New landing page: system-font stack, ~9KB critical CSS inline, zero JS frameworks, no external requests, `loading="lazy"` below-fold, explicit width/height (CLS), `<link rel="preconnect">` none needed |
| PERF-09 | No perf budget / CI check (Lighthouse ≥95 target) | Medium | Backlog: Lighthouse CI once a real frontend exists |
| PERF-10 | `composer.lock` was missing → cold installs unreproducible and slow | Low | Lock generated this phase |

## Blueprint cache hierarchy vs. now

| Layer | Blueprint | Now |
|---|---|---|
| Browser (immutable assets) | versioned URLs, infinite TTL | n/a (no asset pipeline yet) |
| CDN edge | tagged HTML/API cache | not provisioned |
| Redis | product doc, price, session, rate counters | not provisioned (documented) |
| PostgreSQL | SoR + replicas | single dev DB |

## Phase-appropriate verdict

For a foundation phase the mandatory items are: pagination, eager loading, response cache headers, resolver caching, Redis-ready config, and queued-job discipline for future imports. The first five are implemented this phase; the sixth is enforced by leaving import execution behind a 501 stub rather than a synchronous implementation.
