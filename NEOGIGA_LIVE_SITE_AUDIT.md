# NeoGiga — Live Site Audit

**Date:** 2026-07-07 · Probed from client over HTTPS. Read-only. Server IP 217.217.249.72 (Apache/Virtualmin).

## Domain reachability & TLS

| Domain | Result |
|---|---|
| https://neogiga.com/ | **200** — landing renders |
| https://www.neogiga.com/ | **301 → https://neogiga.com/** then 200 (www→non-www ok) |
| https://backend.neogiga.com/api/v1/marketplaces | **200** — JSON, real data |
| https://admin.neogiga.com/ | **200** → redirects to `/admin` → `/admin/login` |
| https://admin.neogiga.com/admin/login | **200** — admin login page |
| SSL | Let's Encrypt; neogiga.com SAN covers apex+www+admin; backend own cert; valid to 2026-10-04 |

## Security surface

| Check | Result |
|---|---|
| `/.env` | **403** ok |
| `/.git/config` | **403** ok |
| `/storage/logs/laravel.log` | **403** ok |
| APP_DEBUG (prod) | **false** (config-cached) |
| Admin exposed? | No — `/admin` fail-closed (302→login), admin API 401 without token |
| Admin indexable? | No — `X-Robots-Tag: noindex, nofollow, noarchive` on admin vhost |
| Security headers | X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy, Permissions-Policy, HSTS, CSP (non-API) |
| CORS | Restricted to neogiga.com / www / admin (verified: allowed echoed, evil origin denied) |

## SEO signals

| Check | Result |
|---|---|
| robots.txt | **200** |
| sitemap.xml | **200**, **178 `<loc>`** (home + 177 categories) |
| Category URLs | `https://neogiga.com/categories` -> **404**; `.../categories/semiconductors` -> **404** |
| Landing meta/JSON-LD | Present (title, description, OG, hreflang, Organization/WebSite JSON-LD) |
| Mobile viewport | Present |

### Primary live defect — sitemap vs routes mismatch
The sitemap lists **177 `/categories/{slug}`** URLs, but **no route serves them** (they 404). The frontend category pages (`frontend/categories/{index,show}.blade.php` + `Web/CategoryController` + routes) were **built locally last session but never deployed** (deploy was interrupted by a tooling outage). Impact: search engines crawl 177 dead URLs -> wasted crawl budget, soft-404 signals, zero category landing pages indexed.

**Fix (P0, low effort):** deploy the already-built files to `/home/neogiga/laravel/current`, `chown neogiga`, `route:clear && route:cache && view:cache`, verify `/categories`=200 + a slug=200 + bad slug=404. No new code required.

## Not tested here (needs browser tooling / login)
- Full Lighthouse/page-speed, console/network errors (no headless browser run this pass).
- Authenticated admin dashboard rendering (verified functionally last session: login->dashboard 200, all sections 200).

## Verdict
Live infra is **healthy and secure** — no exposed secrets, no debug leak, correct redirects, isolated DB, gated admin. The **only material live problem is the SEO 404 regression** above, which is a deploy-only fix.
