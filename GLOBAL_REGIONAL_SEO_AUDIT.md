# Global and Regional SEO Audit

Generated: 2026-07-13

## Current State

- Locale-prefixed public routes exist for home, products, categories, manufacturer, RFQ, LMS, seller, distributors, and AI commerce.
- Marketplace SEO renderer and marketplace domain SEO tests exist.
- Sitemaps are gated to public visibility for product URLs.
- Product JSON-LD and breadcrumb JSON-LD exist.

## Completed In This Pass

- Brand and manufacturer identity links now have canonical routes.
- Product JSON-LD includes brand URL and manufacturer organization when normalized.

## Gaps

- Brand page and manufacturer page hreflang/canonical coverage needs sitemap integration.
- Product schema should avoid price offers when no verified public offer exists.
- Regional brand/category SEO page generation remains incomplete.

## Next Fixes

1. Add brand/manufacturer URLs to sitemap only when public and useful.
2. Add regional landing SEO generator command.
3. Add SEO audit dashboard for missing title, description, schema, canonical, and hreflang.
