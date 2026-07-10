# REGIONAL_SEO_GAP_REPORT (2026-07-10)

| Requirement | State | Evidence / gap | Priority |
|---|---|---|---|
| Self-referencing canonical | ✅ Exists on product pages (`<link rel="canonical">` on `/products/{slug}`, built this cycle) | Not confirmed on category/manufacturer/landing pages | P1 |
| Reciprocal hreflang | ⚠️ Partial | `GlobalMarketplaceContextService::hreflangLinks()` generates links across the 3 existing domains; needs to extend to 25 path-based marketplaces once Stage 1 routing exists | P1 (Stage 4, deferred) |
| x-default | ✅ Exists | **Correction**: confirmed present in `GlobalMarketplaceContextService::hreflangLinks()` — always emits an `x-default` entry pointed at neogiga.com. (Marked "unconfirmed" earlier in this audit before the full file was read during Stage 1 implementation.) | — |
| Marketplace-specific Product/Offer schema | ⚠️ Partial | Product JSON-LD exists (name/sku/mpn/brand/category/url); no `Offer` block with marketplace-specific price/currency/availability found | P2 |
| Country sitemap shards | ❌ Missing | `SitemapController` produces a single flat sitemap; no per-marketplace/country sharding | P2 (Stage 4) |
| Manufacturer/category/product sitemap segmentation | ❌ Missing | same flat-sitemap limitation | P2 (Stage 4) |
| Image/video sitemap | ❌ Missing | not found | P3 |
| `llms.txt` | ✅ Exists | from Phase 0-R | — |
| Breadcrumbs | ✅ Exists | `BreadcrumbList` JSON-LD confirmed on `/products/{slug}` | — |
| CollectionPage / ItemList | ❌ Missing | not found on `/products` listing page | P2 |
| Course / HowTo schema | ❌ Missing | LMS pages don't appear to emit `Course` schema (not confirmed this cycle — flag for follow-up read) | P2 |
| FAQ schema | ✅ Exists (Phase 0-R landing page) | not confirmed on newer pages | P3 |
| Noindex rules for thin/duplicate pages | ✅ Partial precedent exists | JLCPCB-imported products correctly set `seo_meta.robots = "noindex,nofollow"` while `pending_review` — the *pattern* for "don't index thin/unreviewed content" is already established and should be reused for any new country landing pages that lack real local content | P1 |
| Crawler-safe redirects (no forced redirect for verified bots) | ❌ Not yet implementable | no redirect logic exists yet at all (Stage 1 builds recommend-only, not force-redirect, satisfying this requirement by construction — see COUNTRY_LOCALIZATION_GAP_REPORT) | — |

## Recommendation
SEO scaling to 25 countries is fundamentally a **Stage 4** activity (per the prompt's own release
order) and is not part of this cycle's implementation scope. The one SEO-adjacent thing Stage 1
must get right by construction: **new country marketplaces must not generate any indexable URLs
until they have real content** — satisfied automatically since Stage 1 only adds path-prefix
*routing infrastructure* and a country selector, not auto-generated per-country landing pages.
