# Schema Mapping

The ingestion layer extends, rather than replaces, the current canonical catalogue.

| Concern | Existing NeoGiga table | New additive table |
| --- | --- | --- |
| Canonical sellable product | `products` | `supplier_products` source listing and provenance |
| Source registry/batches | `catalog_sources`, `catalog_import_batches` | `supplier_sources`, `catalog_import_runs`, items/checkpoints/snapshots/events |
| Category | `product_categories` | `supplier_category_mappings` |
| Specs | `product_specs`, `product_specifications` | normalized definitions, aliases, and `product_specification_values` |
| Compatibility | `product_compatibility` | `compatibility_platforms`, `supplier_product_compatibilities` |
| Media/documents | `product_images`, `product_datasheets` | `supplier_product_assets` provenance and rights state |
| Marketplace overlay | marketplace prices/inventory | `country_products` remains pending review and has no imported price by default |

Canonical matching is exact GTIN (when available), brand/manufacturer plus normalized MPN, supplier source product ID/SKU, then canonical URL. Title similarity never auto-merges products.
