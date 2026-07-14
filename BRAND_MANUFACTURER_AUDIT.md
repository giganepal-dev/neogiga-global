# Brand and Manufacturer Audit

Generated: 2026-07-13

## Current State

- `product_brands` and `ProductBrand` exist.
- Product rows already link to brands through `brand_id`.
- Manufacturer landing route existed only as inferred `/manufacturer/{slug}` under localized routes and `/manufacturers/{slug}` globally.
- Product pages previously displayed brand as manufacturer-like text without a dedicated manufacturer entity.

## Completed In This Pass

- Added `manufacturers` and `manufacturer_aliases` tables.
- Added nullable `products.manufacturer_id` and `product_brands.manufacturer_id`.
- Added `App\Models\Manufacturer` and `App\Models\ManufacturerAlias`.
- Added product and brand manufacturer relationships.
- Added canonical `/manufacturer/{slug}` and `/brand/{slug}` routes.
- Added legacy plural redirects for `/manufacturers/{slug}` and `/brands/{slug}`.
- Updated product detail JSON-LD to include clickable brand and manufacturer identities.

## Remaining Gaps

- Existing free-text `manufacturer_name` values must be normalized into manufacturer records.
- Brand-to-manufacturer mapping requires source-backed review.
- Manufacturer aliases need importer/backfill commands with confidence scores.
