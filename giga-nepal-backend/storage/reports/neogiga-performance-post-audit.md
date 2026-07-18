# NeoGiga Performance Post-Implementation Audit

**Date:** 2026-07-18
**Auditor:** Claude Code (read-only)
**Branch:** `performance/neogiga-hard-cache-optimization` (e1ee00d)
**Production:** neogiga.com (217.216.78.56), giganepal.com (217.217.249.72:2222)

---

## 1. Executive Summary

| Metric | Value |
|--------|-------|
| **Overall implementation status** | ~15% |
| **Performance optimization status** | Early stage — foundational work only |
| **Production readiness** | NOT production-ready for cache layer |
| **Is the website measurably faster?** | No — no cache layer active in production |
| **Hybrid cache strategy working?** | No — Redis unused, file cache unused for pages |
| **Private/regional data at risk?** | Low risk currently (no cache active), but no isolation if enabled |
| **Critical blockers** | 3 |
| **High-priority remaining work** | 8 |

### Ratings Legend
- ✅ Complete and verified
- ⚠️ Complete but not verified in production
- 🔶 Partially complete
- ❌ Not implemented
- 🚫 Implemented incorrectly
- 🚧 Blocked
- N/A Not applicable

---

## 2. Git and Deployment Verification

| Item | Status | Detail |
|------|--------|--------|
| **Current branch** | `performance/neogiga-hard-cache-optimization` | |
| **HEAD commit** | `e1ee00d` | perf: cache category descendant traversal + category page sort/pagination |
| **Files changed** | 8 files, +307/-55 | Category controller, ProductCategory model, brand-logos view, category show view, admin layout, routes |
| **Uncommitted changes** | None | Clean working tree on tracked files |
| **Deployment status** | NOT DEPLOYED | Branch is local only; production runs `main` (80bdc94) |
| **Production matches repo?** | No | Production commit unknown; likely `80bdc94` or earlier |
| **Last deployment date** | Unknown | No deployment logs found |
| **Deployment path** | SSH → `/home/neogiga/neogiga-global/giga-nepal-backend/` | |
| **Backup location** | None found | `/root/backups/` missing; `/var/backups/` has only system files |
| **Rollback readiness** | None | No rollback scripts, no DB backups visible |

**Git log (last 5):**
```
e1ee00d perf: cache category descendant traversal + category page sort/pagination  [NOT DEPLOYED]
279013c fix: product schema - add merchant return policy, shipping details, GTIN...
80bdc94 perf: complete production performance optimization  [LIKELY IN PRODUCTION]
cee246e Merge pull request #18 (production-stability-audit)
d29c6dd update branch
```

---

## 3. Frontend Architecture (Not Nuxt)

**The frontend is NOT Nuxt.** It is pure Laravel Blade server-side rendering with Vite for CSS/JS bundling. All "Nuxt" audit items are N/A.

| Item | Status |
|------|--------|
| **Framework** | Laravel Blade (SSR via PHP) |
| **JS bundler** | Vite (laravel-vite-plugin) |
| **CSS** | Tailwind CSS 3 |
| **SSR for public pages?** | ✅ Blade renders full HTML server-side |
| **No client-side hydration** | ✅ Vanilla HTML, no JS framework |
| **Code splitting** | N/A (no SPA) |
| **Route-level lazy loading** | N/A |
| **Admin JS in storefront?** | ❌ Admin Vite entry is separate; but no strict boundary check |

### Route Rule Matrix

Since there is no Nuxt `routeRules`, this maps to Blade routes and middleware:

| Route Pattern | Rendering | Cache Header | Cached | Risk |
|---|---|---|---|---|
| `/` (homepage) | Blade SSR | `max-age=300, public, SWR=600` | ❌ `Set-Cookie` present | Cookies break CDN cache |
| `/en/categories/{slug}` | Blade SSR | None | ❌ No cache headers | |
| `/en/products/{slug}` | Blade SSR | None | ❌ No cache headers | |
| `/en/brands/{slug}` | Blade SSR | None | ❌ No cache headers | |
| `/en/search` | Blade SSR | None | ❌ | |
| `/login` | Blade SSR | None | ❌ | |
| `/cart` | Blade SSR | None | ❌ | |
| `/checkout` | Blade SSR | None | ❌ | |
| `/account` | Blade SSR | None | ❌ | |
| `/admin/*` | Blade SSR | None | ❌ | |
| `/api/v1/*` | JSON | Varies | ❌ | |

---

## 4. Server-Rendered Content Audit

### Homepage (`/en`)

| Element | Present in HTML |
|---|---|
| Page title | ✅ `NeoGiga Global \| Electronics and Engineering Marketplace` |
| Meta description | ✅ Present |
| CSS (Tailwind vars) | ✅ Inline in `<head>` |
| Navigation | ✅ Present in HTML |
| Product listings | ✅ Homepage has category/product cards |
| Yandex verification | ✅ `<meta name="yandex-verification">` |
| Google Analytics | ✅ gtag script present |

**Issue:** Homepage sets `XSRF-TOKEN` and `neogiga_session` cookies even for anonymous visitors. This prevents CDN/proxy caching.

### Category Page (`/en/categories/{slug}`)

- ✅ Category name, description, breadcrumb, subcategories, product grid (24 products) all rendered server-side
- ⚠️ No `Cache-Control` header set (not using CachePublicPages middleware)

### Product Page (`/en/products/{slug}`)

- Not directly verified (need valid product slug)

---

## 5. Caching Architecture Audit

### 5.1 Cache Store Configuration

| Setting | Local (.env) | Production (.env) | Recommended |
|---|---|---|---|
| CACHE_STORE | `database` | `file` | `redis` |
| SESSION_DRIVER | `file` | `file` | `redis` |
| QUEUE_CONNECTION | `database` | `database` | `database` (acceptable) |
| REDIS_HOST | Commented out (`#`) | `127.0.0.1` | `127.0.0.1` |

### 5.2 Redis Status (Production)

| Metric | Value |
|---|---|
| **Running?** | ✅ Yes (Redis 7.0.15) |
| **DBSIZE** | **0 keys** |
| **Memory used** | 919KB |
| **Maxmemory** | 0 (unlimited) |
| **Eviction policy** | `noeviction` |
| **Keyspace hits** | 0 |
| **Keyspace misses** | 0 |
| **Connected clients** | 1 |

**Redis is installed and running but COMPLETELY UNUSED.** Both `CACHE_STORE=file` and `SESSION_DRIVER=file` bypass Redis entirely.

### 5.3 Cache Keys Currently Used (Code Audit)

| Cache Key | TTL | Invalidation | Status |
|---|---|---|---|
| `page:{sha1(url)}` | 300s | Expiry only | ❌ Middleware NOT registered |
| `marketplace:domain:{domain}` | 3600s | `MarketplaceResolverService::clearCache()` | ✅ Working |
| `marketplace:global` | 3600s | Same | ✅ Working |
| `marketplace:public-editions` | 3600s | Admin cache clear | ✅ Working |
| `marketplace:all-editions` | 3600s | Admin cache clear | ✅ Working |
| `marketplace:prefix:{prefix}:*` | 3600s | `MarketplacePathResolver::clearCache()` | ✅ Working |
| `marketplace:host-allowlist` | 300s | Expiry only | ✅ Working |
| `category_parent_child_map` | 86400s | `ProductCategory::booted()` | ✅ Working (new) |
| `categories:tree` | 1800s | Expiry only | ✅ Working (API only) |
| `catalog:brand-version` | Forever | `ProductBrand::booted()` (version bump) | ✅ Working |
| `catalog:brands:{version}:{identity}` | 600s | Version bump | ✅ Working |
| `catalog:facets:{hash}` | 3600s | Expiry only | ✅ Working |
| `catalog:search-summary` | 3600s | Expiry only | ✅ Working |
| `seo:sitemap-version` | Forever | Multiple (brand, catalog, SEO changes) | ✅ Working |
| `seo:sitemap-*` | 3600s | Version bump | ✅ Working |
| `seo:catalog-version:{type}:{id}` | Forever | SEO template changes | ✅ Working |

**Cache invalidation strategy:** Version-bump pattern — `Cache::forever('key-version', timestamp)` makes dependent cache keys change, causing natural expiry.

---

## 6. Cache Key and Regional Isolation Audit

### 6.1 Current State

**No page-level caching is active.** The `CachePublicPages` middleware is dead code — it exists as a file but is NOT registered in `app/Http/Kernel.php` (which doesn't exist) or in any route group.

### 6.2 Isolation Analysis (If Caching Were Enabled)

The `CachePublicPages` middleware uses:
```php
$key = 'page:' . sha1($request->fullUrl());
```

**This is INSUFFICIENT for regional isolation.** `fullUrl()` includes the path and query string but NOT the `Host` header. Two requests to different domains (e.g., `neogiga.com/en/products/foo` and `giganepal.com/en/products/foo`) could potentially collide if the URL structure is the same, though in practice different domains typically won't share the same URLs.

**Missing isolation dimensions:**
- ❌ Marketplace domain
- ❌ Currency
- ❌ Locale
- ❌ Authenticated vs anonymous (session cookie check is absent)
- ❌ Reseller vs retail pricing

### 6.3 Service-Layer Caching

Service-layer caches (marketplace resolution, brand visibility, categories) DO properly separate by domain/identity:
- `marketplace:domain:{domain}` — domain-aware
- `catalog:brands:{version}:{identity}` — includes per-host/per-locale identity
- `catalog:facets:{sha1(filters)}` — filter-aware

**Verdict:** Service-layer caching is properly isolated. Page-level caching is not implemented, and if it were, the current design would have regional leakage issues.

---

## 7. Private Data Cache Safety Audit

### 7.1 Current State

Since `CachePublicPages` middleware is NOT active, there is no page-caching risk currently. However, the middleware design has gaps:

| Route Type | Middleware Skips | Safe? |
|---|---|---|
| `/admin*` | ✅ Yes | Safe |
| `/api*` | ✅ Yes | Safe |
| `/cart*` | ✅ Yes | Safe |
| `/checkout*` | ✅ Yes | Safe |
| `/login*` | ✅ Yes | Safe |
| `/register*` | ✅ Yes | Safe |
| `/account*` | ✅ Yes | Safe |
| `/logout*` | ✅ Yes | Safe |
| Authenticated user browsing public pages | ❌ Not skipped | **UNSAFE** — would cache session-specific content |

**Critical gap:** The middleware does NOT check for authenticated users. A logged-in user browsing a product page could have their page (with "Welcome, Name" header) cached and served to anonymous users.

### 7.2 Cookie Leakage on Homepage

The homepage (`/en`) sets `XSRF-TOKEN` and `neogiga_session` cookies on every request, including first-visit anonymous users. This means:
- No CDN HIT is possible (Set-Cookie prevents caching)
- Every homepage visit creates a session record
- Session table grows unbounded

---

## 8. Cache Invalidation Audit

### 8.1 Model-Level Invalidation

| Model | Invalidation | Status |
|---|---|---|
| ProductCategory | ✅ `category_parent_child_map` on save/delete | Working |
| ProductBrand | ✅ `catalog:brand-version` + `seo:sitemap-version` bump | Working |
| Product | ❌ None | **MISSING** |
| MarketplaceProductPrice | ❌ None | **MISSING** |
| InventoryStock | ❌ None | **MISSING** |
| ProductImage | ✅ `catalog:product-media-version:{id}` bump | Working |
| Marketplace | ❌ None (manual cache clear) | Adequate |
| SeoMeta | ✅ `seo:catalog-version:{type}:{id}` bump | Working |

### 8.2 Change Event Matrix

| Change Event | Cache Keys Invalidated | Over-clearing | Stale-data Risk |
|---|---|---|---|
| Product updated | None | N/A | **HIGH** — product name/description stale for 5 min |
| Price updated | None | N/A | **CRITICAL** — wrong price shown until page cache expires |
| Stock updated | None | N/A | **HIGH** — out-of-stock product shown as available |
| Category updated | `category_parent_child_map` | No | Low |
| Brand updated | `catalog:brand-version`, `seo:sitemap-version` | Minimal | Low |
| Product image updated | `catalog:product-media-version:{id}` | No | Low |
| SEO updated | `seo:catalog-version:{type}:{id}` | No | Low |

---

## 9. Database Performance Audit

### 9.1 Production Database Issues

**Active error in production logs:**
```
SQLSTATE[42P10]: SELECT DISTINCT ON expressions must match initial ORDER BY expressions
```
This affects marketplace product queries — a Laravel ORM ordering incompatibility with PostgreSQL.

### 9.2 Category Hierarchy Query

The new `CategoryController::show()` uses:
- `ProductCategory::descendantIds()` — cached parent→child map + BFS (GOOD)
- `Product::whereIn('category_id', $categoryIds)` — single WHERE IN query (GOOD)
- `LengthAwarePaginator` — OFFSET-based pagination (ADEQUATE for small categories)

**Improvement over previous:** Previous code only went 1 level deep (parent + immediate children). New code traverses full depth.

### 9.3 Index Audit

From migration analysis, key indexes exist on:
- `products.category_id` (implicit via foreign key)
- `marketplace_product_prices (product_id, marketplace_id)`
- `inventory_stocks (product_id)` (implicit)
- `product_brands` — no explicit search indexes
- `product_search_documents` — denormalized search table

**Missing indexes (likely):**
- Composite index on `products (category_id, is_published, created_at)` for category listing
- `marketplace_product_prices (product_id, marketplace_id, base_price)` for sorted listing
- `inventory_stocks (product_id, quantity_available)` for stock filtering

### 9.4 N+1 Query Risk

| Location | Risk | Status |
|---|---|---|
| Category::show() `$products->load('images')` | ✅ Eager-loaded | OK |
| Category::show() `$products->load('category')` | ✅ Eager-loaded | OK |
| `$p->images->first()` in Blade loop | ✅ Already loaded | OK |
| `descendantIds()` (new) | ✅ Cached single-query + BFS | OK |
| `breadcrumb()` | ✅ `loadMissing('parent.parent...')` 12 levels | OK |

---

## 10. Image Optimization Audit

| Feature | Status |
|---|---|
| WebP generation | ✅ Generated server-side (Elecforest imports) |
| AVIF generation | ✅ Generated server-side (Elecforest imports) |
| WebP/AVIF in `<picture>` tags | ❌ Not used in views |
| `srcset` | ❌ Not used |
| `loading="lazy"` | ✅ Used on all product images |
| `width`/`height` attributes | ✅ Present (480x360 standard) |
| Placeholder fallback | ✅ `neogiga-product-placeholder-2026.png` |
| Responsive sizes | ❌ All images served at single size |
| Image CDN | ❌ No CDN configured |
| Preload primary image | ❌ Not used |

**Derivatives generated but never used in frontend:** `ElecforestMediaImporter` generates WebP/AVIF at 160w, 400w, 800w, 1200w but Blade templates only reference `$image->publicUrl()` which returns the original URL.

---

## 11. Search Performance Audit

| Item | Status |
|---|---|
| **Search engine** | PostgreSQL only (no Meilisearch/Typesense/Elasticsearch) |
| **Search table** | `product_search_documents` (denormalized) |
| **Method** | LIKE/ILIKE queries against `searchable_text` |
| **Facets** | `product_facet_values` table |
| **Caching** | Facets cached 1h (`catalog:facets:{hash}`) |
| **Index freshness** | Rebuild via `CatalogSearchRebuildService` (chunked 500/batch) |

---

## 12. SEO Integrity Audit

| Element | Status |
|---|---|
| Product titles | ✅ Template-based per marketplace |
| Meta descriptions | ✅ Template-based per marketplace |
| Canonical URLs | ✅ Per-marketplace with hreflang |
| hreflang | ✅ Generated by `GlobalSeoI18nService` |
| Structured data (BreadcrumbList) | ✅ Present on category pages |
| Structured data (ItemList) | ✅ Present on category pages |
| Sitemaps | ✅ Cached 1h with version-bump invalidation |
| Robots | ✅ Category pages have robot directives |
| Open Graph | ⚠️ Default OG image configured; per-product OG in SEO template |
| Regional SEO | ✅ 25 marketplace prefixes with per-country templates |

**No SEO damage detected from performance changes** — SEO pipeline is independent and working.

---

## 13. JavaScript and Asset Audit

| Metric | Value |
|---|---|
| **JS framework** | None (no React/Vue/Alpine in storefront) |
| **CSS framework** | Tailwind CSS 3 via Vite |
| **Total CSS/JS** | ~67KB homepage (all inline/server-rendered) |
| **Google Analytics** | gtag (deferred) |
| **Third-party scripts** | GA only |
| **Font optimization** | System font stack, no web fonts |
| **Duplicate libraries** | N/A (minimal JS) |

---

## 14. Server Health Audit

| Metric | Value | Status |
|---|---|---|
| APP_ENV | production | ✅ |
| APP_DEBUG | false | ✅ |
| PHP version | 8.4.23 | ✅ |
| OPcache | Enabled, `validate_timestamps=0` | ✅ |
| Config cache | `bootstrap/cache/config.php` exists | ✅ |
| View cache | 36 compiled views | ✅ |
| PHP-FPM | Active, dynamic pool, max 80 children | ✅ |
| Queue workers | 4 running (Supervisor) | ✅ |
| Failed jobs | 0 | ✅ |
| HTTP/2 | Supported by nginx 1.24 | ✅ |
| Gzip | Enabled in nginx (minimal config) | ⚠️ |
| Brotli | Not configured | ❌ |
| Disk usage | 17% (32GB/193GB) | ✅ |
| Memory | 31% used (3.4GB/11GB) | ✅ |
| CPU load | 15.86 (elevated) | ⚠️ |
| Swap | 768KB (negligible) | ✅ |
| Open files limit | 1024 | ⚠️ Low for production |
| DB connections | PostgreSQL active (7+ concurrent SELECTs) | ⚠️ |

---

## 15. Tests

```
Tests:    20 failed, 12 skipped, 202 passed (1195 assertions)
Duration: 17.75s
```

All 20 failures are pre-existing: `SQLSTATE[HY000]: General error: 1 no such table: roles` — SQLite in-memory DB missing migrations. Not caused by performance changes.

---

## 16. What Was Actually Implemented (This Branch)

The `performance/neogiga-hard-cache-optimization` branch (NOT deployed) contains:

1. ✅ **Category descendant caching** — `ProductCategory::getParentChildMap()` with 24h cache + in-memory BFS (replaces N+1 queries)
2. ✅ **Category page sort/pagination/stock filter** — sort options, stock filter UI, LengthAwarePaginator
3. ✅ **Brand logos admin page** — `/admin/brand-logos` with logo preview and verification tracking
4. ✅ **Cache invalidation for categories** — `ProductCategory::booted()` busts cache on save/delete
5. ❌ **CachePublicPages middleware** — exists as file but NOT REGISTERED (dead code)
6. ❌ **Redis not configured as cache store** — `CACHE_STORE=file` on production
7. ❌ **No cache for products, prices, stocks** — no invalidation, no page cache for product pages

---

## 17. Critical Issues

### C1. Page Cache Middleware Is Dead Code
- **File:** `app/Http/Middleware/CachePublicPages.php`
- **Problem:** Middleware exists but is not registered in Kernel or routes
- **Impact:** No page-level caching at all — every request hits DB
- **Fix:** Register middleware in route group for public web routes
- **Complexity:** Small

### C2. Redis Completely Unused
- **Server:** Production Redis DBSIZE = 0 keys
- **Problem:** `CACHE_STORE=file`, `SESSION_DRIVER=file` bypass Redis
- **Impact:** File-based cache is slow, doesn't scale, no shared cache across processes
- **Fix:** Set `CACHE_STORE=redis`, `SESSION_DRIVER=redis` in production .env
- **Complexity:** Small (but requires testing)

### C3. Homepage Sets Cookies for Anonymous Users
- **Problem:** Every request (even first-visit anonymous) gets `XSRF-TOKEN` + `neogiga_session` cookies
- **Impact:** CDN cannot cache homepage; session table grows unbounded
- **Fix:** Move CSRF token to form-only scope; use `StartSession` middleware conditionally
- **Complexity:** Medium

---

## 18. High-Priority Issues

### H1. No Product/Price/Stock Cache Invalidation
- Models (`Product`, `MarketplaceProductPrice`, `InventoryStock`) have no `booted()` cache busting
- If page cache were enabled, stale prices/stocks would be served

### H2. CachePublicPages Has No Auth Check
- Would cache authenticated user pages (with personal content)
- Needs `auth()->check()` guard before caching

### H3. Cache Key Doesn't Include Domain/Marketplace
- `sha1($request->fullUrl())` doesn't include Host header
- Regional leakage possible if caching enabled

### H4. PostgreSQL DISTINCT ON Bug in Production
- Errors in `laravel.log`: `SELECT DISTINCT ON expressions must match initial ORDER BY expressions`
- Affects marketplace product listing queries

### H5. No Backups Exist
- No `/root/backups/`, no DB dumps found
- Rollback impossible for current deployment

### H6. CPU Load at 15.86
- PostgreSQL dominant (7+ concurrent SELECT processes)
- Needs query optimization

### H7. Image Derivatives Unused in Frontend
- WebP/AVIF at 4 sizes generated but never served
- All images served at original URL via `publicUrl()`

### H8. No Monitoring or Alerting
- No cache hit-rate monitoring
- No performance regression detection
- Redis INFO shows 0 hits — completely dark

---

## 19. Remaining Work Backlog

### Critical
| ID | Problem | File/Service | Risk | Fix | Complexity |
|---|---|---|---|---|---|
| C1 | CachePublicPages not registered | `app/Http/Kernel.php` (missing) | No page cache | Register middleware | Small |
| C2 | Redis unused (0 keys) | Production `.env` | File cache doesn't scale | Switch to Redis | Small |
| C3 | Cookies on anonymous homepage | `StartSession` middleware | CDN bypass | Conditional session start | Medium |

### High
| ID | Problem | File/Service | Risk | Fix | Complexity |
|---|---|---|---|---|---|
| H1 | No product/price/stock invalidation | `Product`, `MarketplaceProductPrice`, `InventoryStock` models | Stale data | Add model boot events | Small |
| H2 | No auth check in page cache | `CachePublicPages` | Private data leak | Add `auth()->check()` guard | Small |
| H3 | Cache key missing domain | `CachePublicPages` | Regional leakage | Include `request->getHost()` in key | Small |
| H4 | PostgreSQL ORDER BY bug | Marketplace product queries | Errors in logs | Fix ORM query ordering | Medium |
| H5 | No backups | Server | No rollback | Set up DB dump cron | Medium |
| H6 | High CPU load | PostgreSQL | Slow performance | Query optimization, missing indexes | Large |
| H7 | Image derivatives unused | Blade views | Wasted compute | Add `<picture>` with srcset | Medium |
| H8 | No cache monitoring | Infrastructure | Dark operations | Add cache hit-rate logging | Small |

### Medium
| ID | Problem | Fix | Complexity |
|---|---|---|---|
| M1 | No product page cache | Add product page to CachePublicPages scope | Small |
| M2 | No brand page cache | Add brand page to CachePublicPages scope | Small |
| M3 | Gzip config minimal | Tune nginx gzip settings (comp_level, types) | Small |
| M4 | Brotli not configured | Add brotli module to nginx | Medium |
| M5 | No Cloudflare config | Set up CDN caching rules | Medium |
| M6 | Ulimit 1024 | Raise to 65535 | Small |
| M7 | Redis memory overcommit warning | `sysctl vm.overcommit_memory=1` | Small |
| M8 | Search uses PostgreSQL only | Consider Meilisearch for performance | Large |

### Low
| ID | Problem | Fix | Complexity |
|---|---|---|---|
| L1 | No `Cache::tags` usage anywhere | Add when Redis enables tag-based invalidation | Medium |
| L2 | `queue:monitor` scheduled without args | Fix cron command | Small |
| L3 | Scanner noise in nginx error log | Block IP 20.63.63.128 | Small |
| L4 | No preload for primary product images | Add `<link rel="preload">` | Small |
| L5 | No `Cache-Control` on category/product pages | Add response headers | Small |

---

## 20. Final Status Matrix

| Feature | Planned | Implemented | Tested | Production Verified | Status | Remaining |
|---|---|---|---|---|---|---|
| Page caching (CachePublicPages) | ✅ | ❌ (dead code) | ❌ | ❌ | 🚫 | Register middleware + auth check |
| Redis as cache store | ✅ | ❌ (file used) | ❌ | ❌ | 🚫 | Switch env vars |
| Category descendant cache | ✅ | ✅ | ❌ | ❌ | ⚠️ | Not deployed |
| Category sort/pagination | ✅ | ✅ | ❌ | ❌ | ⚠️ | Not deployed |
| Cache invalidation (categories) | ✅ | ✅ | ❌ | ❌ | ⚠️ | Not deployed |
| Cache invalidation (products) | ✅ | ❌ | ❌ | ❌ | ❌ | Add model events |
| Cache invalidation (prices) | ❌ | ❌ | ❌ | ❌ | ❌ | Not planned |
| Cache invalidation (stocks) | ❌ | ❌ | ❌ | ❌ | ❌ | Not planned |
| Regional cache isolation | ❌ | ❌ | ❌ | ❌ | ❌ | Include domain in key |
| Private-route bypass | ✅ | 🔶 | ❌ | ❌ | 🔶 | Missing auth check |
| Image optimization (frontend) | ❌ | ❌ | ❌ | ❌ | ❌ | Use srcset + derivatives |
| Database indexes | 🔶 | 🔶 | ❌ | 🔶 | 🔶 | Add composite indexes |
| Cloudflare/CDN | ❌ | ❌ | ❌ | ❌ | ❌ | Infrastructure setup |
| Cache monitoring | ❌ | ❌ | ❌ | ❌ | ❌ | Hit-rate logging |
| Automated tests (cache) | ❌ | ❌ | ❌ | ❌ | ❌ | Write cache isolation tests |
| Rollback process | ❌ | ❌ | ❌ | ❌ | ❌ | DB backups + deploy scripts |
| Search optimization | ❌ | ❌ | ❌ | ❌ | ❌ | Consider search engine |
| Brotli compression | ❌ | ❌ | ❌ | ❌ | ❌ | nginx config |
| Backups | ❌ | ❌ | ❌ | ❌ | ❌ | Cron DB dumps |

---

## 21. Recommended Next Actions

1. **Immediate (today):** Set up database backups on production server
2. **Critical (this week):** Register CachePublicPages middleware with auth check and domain-aware keys
3. **Critical (this week):** Switch `CACHE_STORE` and `SESSION_DRIVER` to `redis` on production
4. **High (this week):** Add cache invalidation to Product, MarketplaceProductPrice, InventoryStock models
5. **High (this week):** Fix PostgreSQL DISTINCT ON ordering bug
6. **High (this week):** Fix homepage anonymous cookies issue
7. **Medium:** Deploy current branch changes (category descendant cache + sort/pagination)
8. **Medium:** Implement image derivatives in frontend via `<picture>` + srcset
9. **Medium:** Add composite DB indexes for category listing queries

---

*Audit performed read-only. No code, configuration, or services were modified.*
