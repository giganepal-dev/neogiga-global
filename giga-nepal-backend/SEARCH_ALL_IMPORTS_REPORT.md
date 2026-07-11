# Search All Imports Report

Date: 2026-07-11

## Production Change

- Changed JLCPCB search/facet rebuilds to index all imported rows, not only rows with `review_status=approved`.
- Removed approved-only read filters from catalog search documents and facets.
- Converted imported products with `visibility_status=hidden` to `marketplace_only` so they are searchable/listable in the marketplace without making them sitemap-public.
- Updated frontend/admin copy to say imported catalog rows are searchable and SEO publication is controlled separately.

## Backup

- Production backup: `/home/neogiga/backups/neogiga_pre_all_imports_searchable_20260711T043838Z.dump`

## Final Production Counts

- Search documents: 19,947
- Search facets: 139,629
- Visible marketplace products: 19,948
- Public products: 25
- Marketplace-only products: 19,923

## Smoke Tests

- `/products`: 200, 19,948 products
- `/products?q=resistor`: 200, 3,898 products
- `/products?q=capacitor`: 200, 2,542 products
- Product page search summary: `19,947 searchable / 19,947 total`

## Notes

- The search index now includes pending-review imports.
- Sitemap/SEO publication remains separate from marketplace search/listing.
