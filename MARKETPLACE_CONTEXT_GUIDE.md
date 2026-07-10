# MARKETPLACE_CONTEXT_GUIDE

How NeoGiga resolves "which marketplace is this request for" (Global Commerce Stage 1, built and live in this cycle).

## Services
| Service | Role |
|---|---|
| `App\Services\Marketplace\MarketplacePathResolver` | Matches the URL's first path segment against seeded `url_prefix` values. Only matches `is_active=true` marketplaces via `resolve()`; `byPrefix($prefix, activeOnly: false)` looks up ANY marketplace (used by the landing page). Cached 1h per prefix. |
| `App\Services\MarketplaceResolverService` | Pre-existing. Domain-based lookup (`getByDomain()`), cached 1h per host. Falls back to the GLOBAL marketplace. |
| `App\Services\Marketplace\MarketplaceUrlGenerator` | Builds a canonical URL for a marketplace: prefers an active branded domain, falls back to `https://neogiga.com/{url_prefix}`. |
| `App\Services\Marketplace\GlobalMarketplaceContextService` | Orchestrator. `context(Request): array` is the one entry point every controller calls. |

## Resolution order (`GlobalMarketplaceContextService::context()`)
1. **URL path prefix** (`/in`, `/np`, …) — `MarketplacePathResolver::resolve()`
2. **Cookie preference** (`ng_marketplace_choice`) — set by `MarketplacePreferenceController::store()`
3. **Authenticated user preference** — reads `$user->marketplace_id` defensively; inert today (no such column exists yet), starts working automatically if one is added later. Not a Stage 1 deliverable, just a safe placeholder in the chain.
4. **Domain rules** — `MarketplaceResolverService::getByDomain()`
5. **Edge-country / GeoIP signal** — Cloudflare's `CF-IPCountry` header, then `Accept-Language` region subtag, matched **data-driven** against active marketplaces by `country_code` (no hardcoded country list — this generalizes a mechanism that pre-existed for India/Nepal only). This step feeds `recommended`, not `current` — see below.
6. **Global fallback** — the marketplace with `global_fallback=true`.

**Only step 5's output ever suggests a marketplace switch to the user (`recommended` + `show_recommendation`), and even that never redirects — it's an in-page recommendation banner the existing frontend already renders.** Steps 1-4 and 6 all resolve `current` directly, silently, with no visible interruption.

## Consuming the context
```php
$context = app(GlobalMarketplaceContextService::class)->context($request);
// $context['current']       -> Marketplace|null (the resolved marketplace)
// $context['recommended']   -> array|null (edition payload, may differ from current)
// $context['editions']      -> Collection of ACTIVE marketplace payloads
// $context['currency_code'], ['country_code'], ['locale'], ['hreflang']
```
For the country selector (shows ALL 25, including preview): `app(GlobalMarketplaceContextService::class)->allEditions()`.

## The `/{prefix}` landing route
`GET /{prefix}` (25 codes only, `whereIn`-constrained — cannot collide with any existing route)
→ `MarketplaceLandingController::show()` → `frontend.marketplace.landing`. Preview marketplaces
render "Coming soon" with no storefront; active-but-domain-based ones (NEPAL/INDIA today) link out
to their real domain. This is purely informational — visiting `/bd` does not change `current` for
subsequent requests and does not redirect anyone.

## Extending to full prefixed storefronts (Stage 4, not built yet)
Today only the landing page exists under `/{prefix}`. To make `/in/products` etc. work, wrap the
public route group in a prefix-aware layer once localized catalog content exists — do not simply
duplicate every route 25×. See NEXT_GLOBAL_COMMERCE_BACKLOG.md.
