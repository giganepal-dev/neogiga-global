# NeoGiga — SEO Gap Report

**Baseline:** Blueprint §42 (SEO Architecture), §43 (LLM Discoverability) · **Date:** 2026-07-06

## Current state (before this phase)

| Item | Found |
|---|---|
| Landing page | Stock Laravel welcome page, `<title>Laravel</title>` |
| Meta description / canonical / OG / Twitter | None |
| JSON-LD structured data | None |
| robots.txt | Allow-all, no `Sitemap:` line, no AI-crawler policy |
| sitemap.xml | Missing |
| llms.txt | Missing |
| hreflang / international | Nothing (critical for .com/.in/giganepal.com strategy) |
| Semantic HTML | Stock page only |
| SSR | Blade is server-rendered ✅ (satisfies "SSR-compatible" for the Laravel-served pages) |
| Core Web Vitals | Stock page ships ~40KB inline Tailwind reset; no images policy, no lazy-loading conventions |
| SEO config system | None |

## Gaps vs. blueprint

1. **No entity foundation:** `Organization` + `WebSite` JSON-LD are prerequisites for knowledge-graph presence (§42 authority engine). Missing.
2. **International SEO unimplemented:** ccTLD strategy (neogiga.com global hub, neogiga.in, giganepal.com) requires hreflang clusters with `x-default` → .com and *no forced geo-redirects*. The `MarketplaceResolverService` correctly resolves by host (no redirect) — good base; nothing emits hreflang.
3. **No sitemap infrastructure:** Blueprint requires sharded sitemaps from `seo-svc` with lastmod-from-events; foundation needs at least a dynamic `/sitemap.xml`.
4. **LLM discoverability (§43):** no llms.txt, no AI-crawler rules in robots.txt, no Q&A-shaped content.
5. **Schema markup for commerce:** `Product`+`Offer`, `BreadcrumbList`, `FAQPage`, `Course`, `HowTo` templates required; nothing exists. `product_seo_meta` and `seo_meta` JSON columns exist in schema (good), never rendered.
6. **Meta template system:** Blueprint wants per-page-type title/description templates with overrides; needs an `config/seo.php` + helper.

## Implemented this phase (foundation)

- ✅ `config/seo.php` — central SEO config (site names per marketplace, title templates, social handles, hreflang map for the three domains, default OG image).
- ✅ NeoGiga landing page (`resources/views/landing.blade.php`): semantic HTML5 landmarks, full meta set (title/description/canonical/OG/Twitter), hreflang link tags for com/in/np editions + `x-default`, JSON-LD `Organization`, `WebSite` (+SearchAction), `BreadcrumbList`, `FAQPage` placeholder; system-font stack, no external JS, inline critical CSS, reserved image boxes (CLS-safe), lazy-loaded below-fold sections.
- ✅ `public/robots.txt` — allow-all + explicit GPTBot/ClaudeBot/PerplexityBot allowance on knowledge paths, `Sitemap:` reference.
- ✅ `public/llms.txt` — site purpose, key URLs, contact.
- ✅ `/sitemap.xml` route — dynamic, emits landing + category URLs (extends automatically as catalog pages ship).
- ✅ Country switcher + language switcher placeholders in header (no geo-redirect, suggestion-only pattern).

## Remaining (next phases)

- Product/category/course page templates with `Product`/`Offer`/`Course` JSON-LD once those pages exist (Phase 1).
- Sharded sitemaps + lastmod from events; image/video sitemaps (Phase 2).
- OG-image generation service; RUM/CWV field data collection (Phase 2).
- Per-locale content + `ne-NP`/`hi-IN` hreflang expansion beyond `en-*` (Phase 2).
- Migrate storefront to Next.js SSR/ISR per blueprint when the frontend workstream starts — the Blade landing page is the stopgap that keeps SEO signals live until then.
