# Implementation Plan

Generated: 2026-07-13

## Phase 1: Catalog Identity Foundation

- Add dedicated manufacturer records, aliases, and nullable product/brand links.
- Add brand and manufacturer public pages.
- Link product pages to brand/manufacturer entities.
- Add catalog identity audit command.

## Phase 2: Manufacturer and Brand Backfill

- Normalize free-text manufacturer names into `manufacturers`.
- Link brands to manufacturers only with source-backed confidence.
- Add importer reports for created, updated, skipped, failed, and low-confidence rows.

## Phase 3: Product Family and Variant Separation

- Add/complete product family tables if missing.
- Separate family pages from sellable variants, MPNs, seller SKUs, regional inventory, offers, and regional price overlays.

## Phase 4: Content Rewrite and Review Pipeline

- Add rewrite job/history tables.
- Preserve source provenance and confidence.
- Require human review for low-confidence AI content.

## Phase 5: Regional Inventory and Pricing

- Centralize sellable stock formula.
- Add quantity tiers, delivery estimates, and marketplace-specific content.

## Phase 6: Global SEO

- Generate category, brand, manufacturer, MPN, and regional SEO pages with hreflang/canonical validation.
- Extend sitemap gates.

## Phase 7: Admin Audit Dashboard

- Add catalog completeness, SEO gaps, source gaps, image gaps, stock gaps, and rewrite status cards.
