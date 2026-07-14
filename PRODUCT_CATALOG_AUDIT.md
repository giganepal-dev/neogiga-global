# Product Catalog Audit

Generated: 2026-07-13

## Current State

- Backend catalog model exists at `giga-nepal-backend/app/Models/Marketplace/Product.php`.
- Public product listing/detail pages exist through `ProductPageController`.
- Core product fields exist for SKU, MPN, brand, category, status, price, stock, attributes, SEO metadata, and visibility gates.
- Existing product extensions already add `manufacturer_name`, `visibility_status`, regional stock rows, advanced specifications, documents, reviews, LMS links, alternatives, and source import tables.
- Dedicated manufacturer identity was missing and has now been added as an additive layer.

## Gaps

- Product family vs sellable variant is still partially represented through `products` and `product_variants`; family-level catalog semantics need a stricter model.
- Manufacturer normalization is incomplete until legacy `manufacturer_name` rows are mapped to `manufacturers.id`.
- Some identifier fields are newly scaffolded and require backfill: normalized MPN, GTIN, HS code, ECCN, lifecycle, source lineage, verification date.
- Parent product stock must not be treated as variant stock in future UI/API work; regional inventory rows should be the source of truth.

## Next Fixes

1. Backfill manufacturers from verified product rows and brand records.
2. Add product-family grouping and enforce variant/offer stock separation.
3. Extend admin review screens for identifier completeness.
4. Run `php artisan neogiga:audit-catalog` after migration to generate count evidence.
