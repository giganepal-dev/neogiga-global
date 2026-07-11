# JLCPCB 70K Import Report

Date: 2026-07-11

## Scope

- Source: CDFER JLCPCB/LCSC open parts database
- Mode: NeoGiga canonical scale import
- Requested target: 70,000 source rows, approximately 50,000 additional products
- Product images: NeoGiga placeholder image only; JLCPCB/LCSC product images were not copied or hotlinked because redistribution rights were not verified.

## Safety

- 70,000-row dry run completed with 0 skipped rows.
- Production backup before import: `/home/neogiga/backups/neogiga_pre_jlcpcb_70k_scale_indexed_20260711T052257Z.dump`
- Added production lookup index: `products_brand_normalized_mpn_idx`
- Canonical identity remained manufacturer/brand + normalized MPN.

## Import Result

- Import batch: `067a03ef-d221-4e9d-909f-838063161c45`
- Rows read: 70,000
- Products inserted: 49,933
- Products updated: 20,067
- Source links created: 49,933
- Source links updated: 19,947
- Offers created: 49,933
- Offers updated: 19,947
- Skipped duplicate/conflict rows: 120

## Final Live Counts

- Total products: 69,881
- JLCPCB source-linked products: 69,880
- Product image rows: 69,881
- Products without active image: 0
- Marketplace-only products: 69,856
- Public products: 25
- Search documents: 69,880
- Search facets: 489,160

## Smoke Tests

- `/products`: 200, 69,881 products
- `/products?q=resistor`: 200, 25,313 products
- `/products?q=capacitor`: 200, 7,333 products
- Placeholder image asset: 200

## Notes

- Imported products are marketplace-searchable and use generated SEO metadata.
- Products remain separated from public SEO publication controls; image media is placeholder-only until redistributable product images are sourced.
