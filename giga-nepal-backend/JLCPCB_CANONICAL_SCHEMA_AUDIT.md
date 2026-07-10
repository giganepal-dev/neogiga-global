# JLCPCB Canonical Schema Audit

Date: 2026-07-10

## Current canonical target

The JLCPCB/LCSC ETL is mapped into the existing NeoGiga marketplace catalog, not a separate catalog.

Existing production tables verified before implementation:

| Domain | Canonical table | Strategy |
| --- | --- | --- |
| Brands / manufacturers | `product_brands` | Resolve by normalized slug, insert inactive-review brand only when missing. |
| Categories | `product_categories` | Resolve hierarchy by slug, insert review categories only when missing. |
| Products | `products` | Resolve by `brand_id + normalized MPN`; preserve curated content with `COALESCE` updates only. |
| Specifications | `product_specs` | Upsert by `product_id + name` using lookup/update to avoid duplicates. |
| Datasheets | `product_documents` | Upsert by `product_id + document_type + source_url`. |
| Offers | `catalog_distributor_offers` | Additive JLCPCB/LCSC distributor offer table with unique `distributor + sku`. |
| Provenance | `catalog_sources`, `catalog_import_batches`, `catalog_product_sources`, `catalog_import_errors` | New additive source traceability layer. |

## Missing canonical support

Production had shell `imports` / `import_rows` tables, but they did not provide source-scoped product rollback, source payload hash, per-product review status, or unique source part identity. A safe additive provenance migration was added.

## Rollback support

The rollback boundary is `catalog_product_sources.import_batch_id` plus `catalog_distributor_offers.import_batch_id`. Rollback dry-run reports affected source links and products considered. Real rollback removes source links/offers for the batch and marks the batch `rolled_back`; it does not delete manually curated product records.

## Safety constraints

- No truncates or destructive deletes are used.
- Imported products default to review/hidden state.
- Existing products are matched by canonical identity: `brand_id + normalized MPN`.
- Existing product descriptions are only filled when empty.
- Full import is blocked in this execution by CLI guardrails.
