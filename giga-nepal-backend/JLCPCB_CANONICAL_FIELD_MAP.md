# JLCPCB Canonical Field Map

| Source / ETL field | Canonical target | Conversion | Conflict strategy | Rollback identifier |
| --- | --- | --- | --- | --- |
| `source_part_id` | `catalog_product_sources.source_part_id` | text | unique with `source_id`; update source link | `import_batch_id` |
| `source_part_id` | `products.sku` | `NG-{source_part_id}` | only on new product | source link |
| `mpn` | `products.mpn` | source text, normalized for lookup | match by `brand_id + normalized MPN` | source link |
| `manufacturer.normalized_name` | `product_brands.slug` | slugified | match existing slug, insert if missing | n/a |
| `manufacturer.display_name` | `product_brands.name`, `products.manufacturer_name` | text | preserve existing product brand/name | n/a |
| `category.path` | `product_categories` hierarchy | slash path to parent/child rows | match slug by hierarchy, insert if missing | n/a |
| `description` | `products.short_description`, `products.description` | text | fill only when existing value is empty | source link |
| `attributes` | `products.attributes` and `product_specs` | JSON plus row-per-spec | update matching `product_id + name` | product source |
| `datasheet_url` | `product_documents.source_url` | URL text | update matching product datasheet | product source |
| `offer.sku` | `catalog_distributor_offers.sku` | text | unique `distributor + sku`, update offer values | `import_batch_id` |
| `offer.price_breaks` | `catalog_distributor_offers.price_breaks` | JSONB | update from source | `import_batch_id` |
| `offer.stock` | `catalog_distributor_offers.stock` | integer | update from source | `import_batch_id` |
| `source_checksum` | `catalog_import_batches.checksum`, product metadata | SHA256 text | new batch per publish run | batch id |
| transformed payload | `catalog_product_sources.source_payload_hash` | SHA256 JSON hash | update source link | batch id |
| raw transformed part | `catalog_product_sources.raw_snapshot` | JSONB | update source link | batch id |
| quality score | `catalog_product_sources.data_quality_score` | decimal | update source link | batch id |
| review status | `catalog_product_sources.review_status`, product metadata | enum-like string | imported rows remain pending review | batch id |

Public SEO pages must not publish thin imported records until review and enrichment are complete.
