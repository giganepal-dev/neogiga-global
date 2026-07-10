# GEO_TARGETED_PROMOTION_GUIDE (2026-07-10)

**Status: DESIGN ONLY — deferred with the promotion engine.** Documents how geo targeting must work
when built, reusing the geo substrate that already exists.

## Existing substrate
- `MarketplaceContextResolver` resolves the active marketplace (URL prefix → domain → cookie → geo).
- `CountryResolver` reads the Cloudflare `CF-IPCountry` edge header and `Accept-Language`, and
  detects crawlers (`isCrawler()`), data-driven across all seeded marketplaces.
- `marketplaces` carry `country_id`; `countries`/`regions`/`cities` exist for finer targeting.

## Rules to implement (codex §10)
- Targeting dimensions: active marketplace, shipping country, billing country, state/province, city,
  postal code, warehouse service area, delivery zone, verified GeoIP (anonymous browsing only).
- **For checkout eligibility use the verified SHIPPING ADDRESS, not GeoIP.** GeoIP may personalize
  browsing but must never override the confirmed delivery destination.
- India-only promo cannot be redeemed for Nepal delivery. Kathmandu promo must validate the eligible
  postal/service area. Warehouse-specific promo requires fulfilment from that warehouse. Global users
  see only promotions valid for their selected marketplace.
- **Crawlers must receive stable, non-personalized promotional metadata** — reuse
  `CountryResolver::isCrawler()` to suppress personalized promo output for bots (matches the existing
  "never redirect/recommend to crawlers" policy).

## Deferred tables/services
`promotion_geographies`, delivery-zone eligibility validation, and the checkout revalidation step
that re-checks geography against the confirmed shipping address (codex §20).
