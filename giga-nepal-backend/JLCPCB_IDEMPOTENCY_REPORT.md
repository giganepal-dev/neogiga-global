# JLCPCB Idempotency Report

Status: pending live pilot rerun.

Expected proof:

- second 1,000-row pilot does not create duplicate products
- second run updates existing `catalog_product_sources`
- product specs are updated by `product_id + name`
- datasheets are updated by `product_id + document_type + source_url`
- offers are updated by `distributor + sku`
