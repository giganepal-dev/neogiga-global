# Product Image Admin Audit

Date: 2026-07-14

## Existing implementation

- `product_images` already stores product ownership, file path, MIME, size, order, primary/active state, alt text, caption, checksum, dimensions, source/license fields, and metadata.
- `Product::images()` exists, but `ProductImage::$fillable` references a nonexistent `url` column and omits most real columns.
- Product admin does not load or manage product images.
- The public controller loads images and derives an Open Graph image, but the product template does not render the collection.
- Existing admin media upload is a general library; it is not product-scoped and has no primary/reorder workflow.
- Existing imported ElecForest media is intentionally inactive pending rights approval. It must not be exposed merely because a file exists.

## Missing controls

- Product-scoped multiple upload, preview/progress, metadata editing, reorder, primary selection, replacement, and safe deactivation.
- Product ownership validation, `catalog.manage` authorization, API equivalents, atomic primary/reorder updates, duplicate checks, and audit records.
- Consistent storage URL resolution for absolute legacy URLs, `/storage` paths, and configured public/S3 disks.
- Active-only public gallery, responsive thumbnails/enlarged view, useful alt text, and layout-stable placeholder fallback.

## Upgrade decision

Reuse `product_images`; add nullable provenance/storage governance columns only. Preserve inactive and historical rows. “Remove” will deactivate the row and preserve the underlying file/data; shared files are never deleted.

## Implemented outcome

- Reused the existing table and added only missing nullable provenance/storage/region fields through `2026_07_14_170000_add_product_media_brand_and_seo_governance`.
- Added `ProductImageManager`, permission-gated web/API controllers, product-admin drag/drop upload with progress, thumbnails, reorder, primary selection, metadata, replacement and safe deactivation.
- Added ownership checks, MIME/signature/dimension/size/duplicate validation, atomic primary/order changes, audit entries and cache invalidation.
- Added active-only storefront/API images, responsive gallery/thumbnails, enlarged viewing and the supplied-logo placeholder family. Existing legacy placeholder paths remain valid through the compatibility wrapper.
- Production retained exactly 85,392 product-image rows across deployment; no existing file or image row was deleted.
