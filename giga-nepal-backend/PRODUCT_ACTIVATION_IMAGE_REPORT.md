# Product Activation and Image Backfill Report

Date: 2026-07-11

## Production Action

- Activated all draft products by setting `products.status` to `approved`, which is the active catalog state allowed by the production database constraint.
- Preserved `approval_status` and `visibility_status` so pending-review/hidden imported records were not made public.
- Inserted one active primary placeholder image row for every product missing an active image.
- Placeholder asset: `/images/products/neogiga-component-placeholder.svg`

## Backup

- Production backup: `/home/neogiga/backups/neogiga_pre_activate_products_images_20260711T043313Z.dump`

## Final Production Counts

- Draft products remaining: 0
- Product image rows: 19,948
- Products without active image: 0
- Public products: 25
- Hidden products: 19,923

## Notes

- The production schema does not allow a literal `active` value in `products.status`; allowed catalog-active status is `approved`.
- Placeholder images are temporary NeoGiga-branded media records. Manufacturer/product-specific images still require sourced assets and media review.
