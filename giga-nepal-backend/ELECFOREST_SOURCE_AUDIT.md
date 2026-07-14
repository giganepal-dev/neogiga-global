# ElecForest Source Audit

Generated: 2026-07-14 09:48 Asia/Kathmandu

## Source file

- Preferred working copy: `storage/app/imports/elecforest-products.jsonl`
- Original discovered copy: `/Users/ashokdhamala/Downloads/elecforest_scraper_v2/output_v2/checkpoint_clean.jsonl`
- Size: 7,074,306 bytes
- SHA-256: `14a04e1001d9d02e787150c33ff3c6970677ed0332b0448475fce6f44b26409c`
- Media type and encoding: `application/json; charset=utf-8`
- Readable: yes

## Validation totals

| Metric | Result |
| --- | ---: |
| Physical lines | 3,178 |
| Valid JSON objects | 3,178 |
| Malformed lines | 0 |
| Invalid UTF-8 lines | 0 |
| Rows with valid raw source URLs | 3,153 |
| Rows with deterministic source URL recovered from slug | 25 |
| Normalized unique source URLs | 3,178 |
| Duplicate source URL groups / extra rows | 0 / 0 |
| Rows with raw supplier SKU text | 3,177 |
| Raw duplicate supplier SKU groups / extra rows | 5 / 75 |
| Rows with usable normalized supplier SKU | 3,105 |
| Actionable duplicate supplier SKU groups / extra rows | 4 / 4 |
| Rows with a dedicated MPN field | 0 |
| Duplicate MPN groups / extra rows | 0 / 0 |
| Rows with raw image URL data | 3,178 |
| Rows with filtered product-image candidates | 3,176 |
| Raw image URLs | 32,522 |
| Filtered product-image candidates | 9,801 |
| Repeated storefront/shipping/payment assets excluded | 22,721 |
| Rows with raw description text | 3,178 |
| Rows with usable description after promotional-spam removal | 3,177 |
| Rows with main category | 3,178 |
| Rows with subcategory | 2,729 |
| Rows with numeric price field | 3,178 |
| Rows with currency | 3,177 |
| Rows with stock status | 3,177 |

The duplicate SKU groups include four two-row numeric collisions and the scraper sentinel value `SKU:` on 72 rows. `SKU:` is treated as missing, never as a shared identity. Colliding supplier SKUs cannot be merged without a verified manufacturer/MPN or approved cross-reference; their source URL remains the deterministic record identity and their NeoGiga SKU receives a short source hash suffix.

Twenty-five `product_url` values are malformed scraper artifacts, but every affected row has a valid product slug. The importer records the raw value unchanged and derives `https://elecforest.com/products/{slug}` as the normalized source-page URL with reduced confidence. No URL is derived when the slug is absent or identifies the collection page.

The raw image lists contain 22,721 repeated non-product storefront assets (shipping-carrier logos, payment logos, a divider and a shared promotional image). Those exact basenames are retained in raw provenance but excluded from product media and network downloads. The remaining 9,801 candidate URLs are subject to HTTPS allowlisting, public-IP resolution, redirect, byte-size, MIME, signature and dimension validation.

## Fields present on every object

`breadcrumb`, `compare_at_price`, `currency`, `description`, `generated_tags`, `image_urls`, `main_category`, `price`, `product_name`, `product_url`, `quantity_text`, `scraped_at_utc`, `site_tags`, `sku`, `slug`, `source_method`, `stock_status`, `subcategory`, `variants`.

The fields are structurally present on all rows, but several are empty. `variants` and `breadcrumb` have no non-empty values. The export has no dedicated brand, manufacturer, MPN, barcode, GTIN, EAN, UPC, applications, package contents, warranty, datasheet, or structured-specification fields. Those values must remain nullable or review-required rather than inferred as facts.

## Main category distribution

| Source category | Rows |
| --- | ---: |
| Modules | 703 |
| Sensors | 688 |
| Electronic Components | 607 |
| Others | 432 |
| Accessories | 342 |
| 3D Printer | 267 |
| Raspberry PI | 99 |
| Kits | 26 |
| Tools | 14 |

## Coverage and safety conclusions

- The file is complete enough for deterministic record ingestion and source-price/source-availability observations.
- The first row is a collection page (`https://elecforest.com/products`, title `All Products`) rather than an individual sellable product. It must be retained in the run audit and rejected from canonical product creation.
- Supplier prices are observations only. They must not update `products.base_price`, marketplace prices, vendor prices, country prices, or any NeoGiga selling-price field.
- Supplier stock text is external availability only. It must not update product stock, warehouse stock, inventory stock, reservations, or regional inventory.
- Image URLs are present, but the export does not include redistribution-license proof. Downloads may be stored for internal review with inactive/pending-rights status; they must not become public automatically.
- Brand and manufacturer are not source-backed fields. They remain nullable unless an administrator approves a separate mapping.
- Generated descriptions, applications and SEO are editorial aids only and must carry source notes, confidence, last-updated time and the required “Advisory only” disclaimer.
