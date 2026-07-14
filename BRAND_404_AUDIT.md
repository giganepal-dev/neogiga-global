# Brand 404 Audit

Date: 2026-07-14

## Root causes

1. `/en/brands` is explicitly implemented as a redirect to `/categories`, so the expected directory cannot render.
2. The API supports `/api/v1/brands` and `/api/v1/brands/{slug}`, but the server-rendered frontend does not consume it and has no dedicated brand list template.
3. `/brand/{slug}` and localized `/en/brand/{slug}` use a generic SEO landing controller. It requires `is_active`, but it does not implement marketplace visibility, publication windows, a brand-specific empty state, or dedicated canonical/product pagination behavior.
4. Legacy `/brands/{slug}` redirects to `/brand/{slug}` without retaining the active locale prefix.
5. The sitemap emits `/en/brands/{slug}`, which is the legacy URL rather than the approved canonical `/en/brand/{slug}`.
6. Product links were recently corrected to `/brand/{slug}`, but the destination remains the generic page.

## Required behavior

- `/en/brands` renders eligible active brands in the existing design.
- `/en/brand/{slug}` is canonical; `/en/brands/{slug}` permanently redirects to it.
- A valid eligible brand renders an in-page empty-product state and never 404s solely because local stock/price is absent or products are RFQ-only.
- Cache identity must include host, marketplace, locale, and entity slug; mutations bump the brand cache version.

## Implemented outcome

- `/en/brands` now renders 200 with the existing NeoGiga design; `/en/brand/{slug}` is the canonical detail route.
- Localized and unlocalized legacy plural detail routes permanently redirect to the canonical singular route. Production verification returned 301 from `/en/brands/sunlord` to `/en/brand/sunlord`.
- `BrandVisibilityService` centralizes active state, publication windows, marketplace/country/category visibility and menu/device flags without using stock or price as a page-existence requirement.
- Dedicated brand templates provide published-product pagination and a valid zero-product/RFQ state. The API retains its prior response shape and adds SEO data.
- Sitemap brand URLs, product links and structured data now use the same canonical route and eligibility rules. Production `/en/brands`, `/en/brand/sunlord` and `/api/v1/brands` return 200.
