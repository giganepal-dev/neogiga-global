# ElecForest Database Mapping

Generated: 2026-07-14 (Asia/Kathmandu)

## Upgrade strategy

The import is an additive integration over NeoGiga's existing catalog. Existing `products`, categories, brands, manufacturers, prices, inventory, SEO, source-ingestion, media, review and change-history structures are reused. Migration `2026_07_14_100000_create_elecforest_catalog_import_layer` conditionally extends only missing capabilities and records which resources it created so rollback cannot remove pre-existing tables or columns.

## Source-to-canonical mapping

| ElecForest input | NeoGiga storage | Rule |
| --- | --- | --- |
| Source identity, raw row and hash | `supplier_products`, `catalog_product_sources` | One source record per normalized source product ID; raw JSON retained unchanged |
| Product name and rewritten content | `products`, `product_content_versions` | Deterministic professional rewrite; draft and hidden by default |
| Supplier SKU and source product ID | `product_identifiers` | Stored separately with confidence; duplicate supplier SKUs are marked ambiguous |
| Source price and compare price | `supplier_product_offers`, `supplier_products` | Supplier observation only; never a selling price |
| Source stock text and quantity | `supplier_product_offers`, `supplier_products` | External availability only; never warehouse inventory |
| Main category and subcategory | `supplier_category_mappings`, `product_category_assignments` | Mapped to canonical existing categories or inactive review taxonomy |
| Explicit key/value details | `product_source_specifications`, `product_specs` | Raw and normalized names/values/units retained with source URL and confidence |
| Explicitly labelled applications | `product_applications` | Source-derived, unverified and confidence-labelled |
| Product image URLs | `supplier_product_assets`, `product_images` | Rights-pending and inactive; local SHA-256-deduplicated media only |
| Product SEO | `product_seo_meta`, `products.seo_meta` | Editable draft metadata, schemas, source notes, confidence, timestamp and disclaimer |
| Review conditions | `catalog_review_tasks` | Missing brand/manufacturer, taxonomy, media rights and missing source applications |
| Import execution | `catalog_import_runs`, `catalog_import_items`, `catalog_import_failures` | Resumable line checkpoints, counters, idempotency and retry state |
| Change audit | `catalog_change_events` | Before/after source hashes and run linkage |

## Provenance contract

Canonical imported products retain `source_name`, `source_url`, `source_file`, `source_page_url`, `downloaded_at`, `imported_at`, `data_year`, `license_note`, `confidence_level`, `original_raw_value` and `normalized_value`. Source specifications, identifiers, offers, media and content versions keep their more specific source URL, raw value, normalization and confidence fields.

Every deterministic recommendation or generated description/SEO record stores `source_notes`, `confidence_level`, `last_updated` and the required “Advisory only” disclaimer.

## Isolation guarantees

The importer writes no ElecForest data to `inventory_stocks`, `marketplace_product_prices`, `vendor_product_prices` or `product_country_prices`. Imported products retain `base_price = 0`, `sale_price = null`, `track_inventory = false` and `stock_quantity = 0`. Existing Nepal, India, China, UAE, Global and other regional price/inventory data is therefore not overwritten.

## Added capability tables

- `catalog_import_failures`
- `product_identifiers`
- `product_applications`
- `product_source_specifications`
- `supplier_product_offers`
- `product_category_assignments`
- `product_content_versions`

The migration also added only missing run/checkpoint and draft/SEO fields. Shared source-ingestion tables already present in this database were preserved.
