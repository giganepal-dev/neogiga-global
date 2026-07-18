# NeoGiga Performance Audit — Remaining Work

## Critical (Security, Data Leakage, Production Risk)

### C1. Enable Redis as Cache Store
- **Problem:** `CACHE_STORE=file` on production. Redis running but DBSIZE=0 (unused)
- **Evidence:** `redis-cli DBSIZE` returns 0; `CACHE_STORE=file` in /home/neogiga/neogiga-global/giga-nepal-backend/.env
- **Risk:** File cache doesn't scale; no shared cache across PHP-FPM processes; slow I/O
- **Fix:** Set `CACHE_STORE=redis` and `SESSION_DRIVER=redis` in production .env, test, deploy
- **Complexity:** Small
- **Downtime:** Brief restart or config cache rebuild
- **Dependencies:** Redis already running on 127.0.0.1:6379

### C2. Activate Page Caching for Public Routes
- **Problem:** Only `/en` and `/np` have cache headers; all category/product/brand pages are `no-cache, private`
- **Evidence:** HTTP audit — every `/en/categories/*`, `/en/products/*`, `/en/brands/*` returns `Cache-Control: no-cache, private`
- **Risk:** Every catalog page visit hits database; server overload under traffic
- **Fix:** Verify CachePublicPages middleware is registered in bootstrap/app.php; ensure category/product/brand controllers don't override headers
- **Complexity:** Small
- **Downtime:** None (config/deploy only)

### C3. Fix Anonymous Session Cookies on Public Pages
- **Problem:** Every page (including homepage) sets `XSRF-TOKEN` + `neogiga_session` cookies for anonymous users
- **Evidence:** HTTP response headers show Set-Cookie on all routes
- **Risk:** CDN can never HIT; session table grows unbounded; privacy concern
- **Fix:** Use conditional session start — skip StartSession middleware for anonymous GET on public routes
- **Complexity:** Medium
- **Downtime:** None
- **Dependencies:** None

### C4. Add Product/Price/Stock Cache Invalidation
- **Problem:** `Product`, `MarketplaceProductPrice`, `InventoryStock` models have no cache invalidation
- **Evidence:** Only `ProductCategory` and `ProductBrand` have `booted()` cache busting
- **Risk:** If page cache is enabled, stale product names, prices, and stock status served for 5 minutes
- **Fix:** Add `booted()` with `Cache::forget()` for relevant keys in all three models. Use version-bump pattern for product-related caches.
- **Complexity:** Small
- **Downtime:** None

### C5. Set Up Database Backups
- **Problem:** No backups exist anywhere on production
- **Evidence:** `/root/backups/` missing; only system package states in `/var/backups/`
- **Risk:** Complete data loss in case of server failure
- **Fix:** Add cron job for `pg_dump` + off-server backup (S3 or SSH to second server)
- **Complexity:** Medium
- **Downtime:** None
- **Dependencies:** Off-server storage target

---

## High (Performance Bottlenecks, Broken Features)

### H1. Fix PostgreSQL DISTINCT ON ORDER BY Bug
- **Problem:** `SELECT DISTINCT ON expressions must match initial ORDER BY expressions` errors in production
- **Evidence:** 59 ERROR entries in laravel.log with SQLSTATE[42P10]
- **Risk:** Marketplace product queries fail intermittently
- **Affected:** Product listing with marketplace pricing joins
- **Fix:** Ensure ORDER BY column matches DISTINCT ON column; likely in CategoryController or product listing service
- **Complexity:** Medium
- **Downtime:** None

### H2. Enable Gzip/Brotli Compression
- **Problem:** 70KB HTML pages transferred uncompressed
- **Evidence:** No `Content-Encoding` header on any response; nginx gzip config minimal (directives commented out)
- **Risk:** Wasted bandwidth; slow page loads on mobile/low-bandwidth connections
- **Fix:** Configure gzip in nginx (comp_level 5-6, types text/html/css/js/json); install ngx_brotli if available
- **Complexity:** Small
- **Downtime:** nginx reload (no interruption)
- **Dependencies:** None

### H3. Serve Image Derivatives to Frontend
- **Problem:** WebP/AVIF at 160w/400w/800w/1200w generated but never served
- **Evidence:** `ElecforestMediaImporter` generates derivatives; Blade views use `$image->publicUrl()` (original only)
- **Risk:** Wasted CPU for derivative generation; slow page loads with large images
- **Fix:** Add `<picture>` with `<source>` elements and `srcset` to product card and product page views
- **Complexity:** Medium
- **Downtime:** None
- **Dependencies:** Derivative URLs need to be exposed via `ProductImage` model

### H4. Add Composite Database Indexes
- **Problem:** Category listing queries likely scanning without optimal indexes
- **Evidence:** CPU load 15.86, PostgreSQL dominant; no composite indexes on (category_id, is_published, created_at)
- **Risk:** Slow category pages under load
- **Fix:** Add indexes:
  - `products (category_id, is_published, created_at DESC)`
  - `marketplace_product_prices (product_id, marketplace_id, base_price)`
  - `inventory_stocks (product_id, quantity_available)`
- **Complexity:** Small
- **Downtime:** Index creation is online (PostgreSQL supports CONCURRENTLY)

### H5. Fix Search (Returns 404)
- **Problem:** `/en/search?q=arduino` returns Laravel 404
- **Evidence:** HTTP audit
- **Risk:** Search functionality unavailable — users can't find products
- **Fix:** Verify search route exists and search page controller is registered
- **Complexity:** Small
- **Downtime:** None

### H6. Add Regional Isolation to Cache Keys
- **Problem:** `CachePublicPages` uses `sha1($request->fullUrl())` without Host header
- **Evidence:** Code audit of `app/Http/Middleware/CachePublicPages.php:24`
- **Risk:** Nepal and India product pages could cache-collide if deployed with caching
- **Fix:** Include `$request->getHost()` in cache key: `'page:' . sha1($request->getHost() . $request->fullUrl())`
- **Complexity:** Small
- **Downtime:** None

### H7. Add Auth Check to Page Cache
- **Problem:** `CachePublicPages` doesn't check for authenticated users
- **Evidence:** No `auth()->check()` or `$request->user()` in middleware
- **Risk:** Logged-in user pages (with personal content) would be cached and served to others
- **Fix:** Add `if (auth()->check()) { return $next($request); }` before caching logic
- **Complexity:** Small
- **Downtime:** None

### H8. Fix High CPU Load (PostgreSQL Tuning)
- **Problem:** CPU load 15.86; 7+ concurrent PostgreSQL SELECT processes
- **Evidence:** `top` shows postgres processes consuming 15-17% memory each
- **Risk:** Server overload under traffic spikes
- **Fix:** Review `postgresql.conf` (shared_buffers, work_mem, max_parallel_workers); optimize slow queries
- **Complexity:** Large
- **Downtime:** PostgreSQL restart needed for some config changes
- **Dependencies:** Query profiling results

---

## Medium (Optimization, Secondary Features)

### M1. Implement Responsive Images with srcset
- Use existing derivatives (160w/400w/800w/1200w) in `<picture>` elements
- **Files:** `resources/views/frontend/categories/show.blade.php`, `resources/views/frontend/products/show.blade.php`
- **Complexity:** Medium

### M2. Add Cache Warming
- Pre-warm category, product, and brand page caches after deployment
- **Complexity:** Medium

### M3. Configure Cloudflare CDN
- Set up caching rules, bypass on cookies, cache public pages at edge
- **Complexity:** Medium (infrastructure, not code)
- **Dependencies:** Fix anonymous cookies first (C3)

### M4. Optimize Category Queries
- Reduce N+1 in product card rendering (currently eager-loads images and category)
- **Complexity:** Small

### M5. Add Cache Monitoring
- Track HIT/MISS rates via `X-Page-Cache` header aggregation
- Log Redis hit/miss periodically
- **Complexity:** Small

### M6. Raise ulimit from 1024
- Production server should use 65535 for web server
- **Complexity:** Small

### M7. Fix Redis Memory Overcommit Warning
- `sysctl vm.overcommit_memory=1`
- **Complexity:** Small

### M8. Tune nginx gzip
- Uncomment and configure gzip_vary, gzip_proxied, gzip_comp_level, gzip_types
- **Complexity:** Small

---

## Low (Cleanup, Documentation, Optional)

### L1. Implement Meilisearch or Typesense
- Replace PostgreSQL LIKE/ILIQUERY search with dedicated search engine
- **Complexity:** Large

### L2. Enable HTTP/3 (QUIC)
- Configure nginx for HTTP/3 support
- **Complexity:** Medium

### L3. Add Cache Tags (when on Redis)
- Use Laravel Cache::tags for group invalidation
- **Complexity:** Medium

### L4. Fix queue:monitor cron call
- Add queue names to scheduled command
- **Complexity:** Small

### L5. Block Malicious Scanner IPs
- IP 20.63.63.128 hitting WordPress exploit paths
- **Complexity:** Small

### L6. Add Primary Image Preload
- `<link rel="preload" as="image">` for product page primary image
- **Complexity:** Small

### L7. Add Cache-Control to Missing Routes
- Category and product pages should have `Cache-Control: public, max-age=0, s-maxage=300, stale-while-revalidate=600`
- **Complexity:** Small

---

## Suggested Implementation Order

1. **C5** — Database backups (no dependencies, immediate risk mitigation)
2. **C2 + H6 + H7** — Fix CachePublicPages (auth check + domain key + register) — fixes all public page caching
3. **C1** — Switch to Redis (enables shared cache across processes)
4. **C3** — Fix anonymous cookies (enables CDN caching)
5. **C4** — Product/price/stock invalidation
6. **H2** — Gzip/brotli compression
7. **H4** — Database indexes
8. **H1** — Fix PostgreSQL DISTINCT ON bug
9. **H5** — Fix search 404
10. **H3** — Serve image derivatives
11. **M1-M8** — Secondary optimizations
12. **L1-L7** — Cleanup and optional improvements
