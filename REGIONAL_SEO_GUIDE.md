# REGIONAL_SEO_GUIDE

**Status: existing SEO infrastructure documented; no SEO work was added this cycle beyond keeping it safe.**

See REGIONAL_SEO_GAP_REPORT.md for the full state-by-state table. Summary for implementers:

## What's live today
- Product-page canonical + Product/BreadcrumbList JSON-LD (built in an earlier cycle).
- `SeoLandingController` — manufacturer/mpn/technology/application programmatic landing pages with
  their own schema output.
- `GlobalMarketplaceContextService::hreflangLinks()` — real hreflang generation including
  `x-default`, currently scoped to the 3 domain-based marketplaces.
- Flat `SitemapController` at `/sitemap.xml`, `llms.txt`.

## What this cycle deliberately kept safe
The new `/{prefix}` landing pages (23 preview + India/Nepal) are **not indexable content pages** —
preview marketplaces render `<meta name="robots" content="noindex,follow">`. This directly follows
the pattern already established by the JLCPCB pilot import (products land `noindex,nofollow` while
`pending_review`) — the codebase has a consistent "don't index unreviewed/thin content" convention,
and Stage 1 extends it rather than breaking it.

## What's still missing (Stage 4, not built)
Scaling `hreflangLinks()` to all 25 marketplaces, country sitemap shards, manufacturer/category/
product sitemap segmentation, image/video sitemaps, Offer schema with marketplace-specific price/
currency, CollectionPage/ItemList on `/products`, Course/HowTo schema on LMS pages. None of these
were touched this cycle — see REGIONAL_SEO_GAP_REPORT.md for the full list and NEXT_GLOBAL_COMMERCE_BACKLOG.md for sequencing.
