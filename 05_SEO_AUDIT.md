# 05 SEO Audit

## Executive Summary

SEO readiness is one of the better-developed areas. The app has a server-rendered landing page, central SEO config, JSON-LD, hreflang, OpenGraph, Twitter cards, sitemap, robots.txt, and llms.txt. However, product/category detail pages are not real SSR pages yet; the catalog is mostly API-only, limiting crawlable content depth.

## Current Status

- Home page: `LandingController` + `resources/views/landing.blade.php`.
- JSON-LD: Organization, WebSite, BreadcrumbList, FAQPage.
- Hreflang: global, India, Nepal editions.
- Sitemap: dynamic home + categories if schema exists.
- robots.txt and llms.txt exist.

## Completed

- SSR landing page.
- Default title/description config.
- OpenGraph and Twitter metadata.
- Canonical URL.
- Hreflang cluster.
- FAQ schema.
- AI crawler guidance in `llms.txt`.
- Basic sitemap endpoint.

## Partially Completed

- Product schema: product SEO meta table exists, but SSR product pages are missing.
- Course/HowTo schema: planned, not implemented.
- Internal linking: landing page links to API category endpoints, not human-friendly category pages.
- AI discoverability: `llms.txt` exists but no source summary pages or document citations yet.

## Missing

- SSR category pages.
- SSR product pages.
- Product JSON-LD per product.
- FAQ schema per category/product.
- HowTo schema for projects.
- Course schema for LMS.
- Breadcrumbs beyond home.
- Sharded sitemap for products/categories/projects.
- Core Web Vitals measurement.
- Accessibility audit automation.

## Risk

Medium. The SEO foundation is strong for a landing page but insufficient for marketplace organic growth because product and learning content are not crawlable pages.

## Evidence

- `config/seo.php`
- `app/Http/Controllers/Web/LandingController.php`
- `app/Http/Controllers/Web/SitemapController.php`
- `resources/views/landing.blade.php`
- `public/robots.txt`
- `public/llms.txt`

## Recommendation

Build SSR catalog pages before scaling paid acquisition: category, brand, product, project, lesson, datasheet, FAQ, and comparison pages with structured data.

## Priority

P0: SSR category/product pages.  
P1: Product/FAQ/HowTo/Course schema.  
P2: Sharded sitemaps and internal-link graph.

## Estimated Effort

2-4 weeks for crawlable catalog MVP.  
8-12 weeks for full LLM/SEO content graph.

