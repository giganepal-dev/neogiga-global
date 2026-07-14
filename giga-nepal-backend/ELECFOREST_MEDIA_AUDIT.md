# ElecForest Media Audit

Generated: 2026-07-14 (Asia/Kathmandu)

## Final result

| Metric | Result |
| --- | ---: |
| Raw image URLs in the export | 32,522 |
| Product-image candidates | 9,801 |
| Stored non-product asset references | 22,555 |
| Successfully downloaded candidates | 9,801 |
| Download failures after retry | 0 |
| Local `product_images` rows | 9,776 |
| Products with at least one local image | 3,176 |
| Sellable products without a local image | 1 |
| Unique original SHA-256 hashes/files | 9,687 |
| WebP derivative files | 31,150 |
| AVIF derivative files | 31,150 |
| Active/public imported images | 0 |
| Rights-pending imported images | 9,776 |

The raw-source audit excludes 22,721 repeated shipping, payment and storefront URLs. The stored count is lower because identical URLs within a supplier product are deduplicated before asset rows are created. Likewise, 9,801 successful candidate downloads produce 9,776 product-image rows because a product/checksum pair is stored only once.

## Processing and security

Media downloads require HTTPS, an exact configured ElecForest/CDN host, public DNS resolution and revalidation after redirects. The importer rejects embedded credentials, custom ports, localhost, private/reserved and metadata IPs, oversized streams, invalid MIME/signature combinations, HTML masquerading as images, SVG, and invalid dimensions. Originals are content-addressed by SHA-256 and reused safely across products.

Known shipping/payment/storefront basenames are recorded as `ignored_non_product_asset` with `not_applicable` rights status and never requested over the network. Product candidates retain original URL, source product page, MIME type, dimensions, byte size, checksum, download time, alt text, title, caption, sort order, primary flag and attribution metadata.

## Derivatives and publication state

Every one of the 9,776 local product-image rows has derivative metadata. Widths are bounded to 160, 400, 800 and 1,200 pixels without upscaling, with WebP and AVIF variants generated where a distinct size is possible. The media and derivative queues drained successfully; `jobs` and `failed_jobs` both contain zero rows.

All imported images remain inactive and `pending_review` because the export does not provide a verified public-use license. This is deliberate: products can be reviewed in the admin panel, but images cannot become public until an administrator confirms rights. No source image is hotlinked from a live product page.
