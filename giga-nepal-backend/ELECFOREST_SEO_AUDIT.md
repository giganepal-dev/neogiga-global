# ElecForest SEO Audit

Generated: 2026-07-14 (Asia/Kathmandu)

## Product coverage

All 3,177 sellable imported records have an editable `product_seo_meta` row containing title, 140–160 character meta description, 8–15 deduplicated relevant keywords, canonical URL, Open Graph fields, Twitter fields, robots directive, Breadcrumb JSON-LD, Product JSON-LD, source notes, confidence, last-updated time and the “Advisory only” disclaimer.

Draft canonical URLs use `https://neogiga.com/en/products/{slug}`. Drafts remain `noindex,nofollow` and are excluded by the existing published-product storefront/sitemap scopes. Product JSON-LD intentionally omits Offer, Brand, Manufacturer, aggregate rating and factual FAQ data when those facts are unavailable. A NeoGiga placeholder is used for draft social/schema images until media rights are approved.

## Category and regional behavior

Thirty-seven affected public canonical categories already contain NeoGiga-generated descriptions and SEO; the single affected internal review category is intentionally inactive and `noindex,nofollow`. Existing global/regional URL rendering continues to own locale, canonical and hreflang behavior, so the importer does not create competing regional pages or copy supplier URLs into public content.

Source URLs remain available in admin/audit provenance. Public presentation uses concise source attribution and does not overload pages with raw links.

## Publication SEO gate

Robots changes to `index,follow` only inside successful qualified publication. Because brand/manufacturer and media-rights verification are absent in the export, no imported record qualifies for automatic publication in this run.
