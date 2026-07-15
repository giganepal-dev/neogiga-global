# JLCPCB Field Mapping Report

Date: 2026-07-15 (Asia/Kathmandu)

## Status

No new mapping was applied and no new JLCPCB/LCSC import was run. This report documents the mapping already used for the 69,880 production source links. The raw source is absent from the audited workspace, so the mapping has not been revalidated against a current upstream schema.

## Canonical identity and content mapping

| Source / transformed field | Existing NeoGiga target | Conversion | Existing conflict behavior |
|---|---|---|---|
| Source part ID / LCSC code | `catalog_product_sources.source_part_id` | Text | Unique with `source_id`; source link is updated |
| Source part ID | `products.sku` | `NG-{source_part_id}` for new products | Existing stable SKU is resolved before insert |
| MPN | `products.mpn` | Source text; normalized for identity lookup | Match by brand plus normalized MPN |
| Manufacturer | `product_brands`, `products.manufacturer_name` | Normalized name and slug | Preserve curated product brand/name; create review brand only when missing |
| Category | `product_categories` hierarchy | Source path to parent/child slugs | Match hierarchy; unknown path remains review-gated |
| Description | `products.short_description`, `products.description` | Source text | Fill only when the canonical value is empty |
| Attributes | `products.attributes`, `product_specs` | JSON plus visible/filterable spec values | Upsert by product and spec name |
| Datasheet URL | `product_documents.source_url` | URL text | Upsert by product, document type and source URL |
| Distributor SKU | `catalog_distributor_offers.sku` | Text | Unique with distributor |
| Price breaks | `catalog_distributor_offers.price_breaks` | JSON | External offer observation only |
| Supplier stock | `catalog_distributor_offers.stock` | Integer when parseable | Never treated as NeoGiga warehouse inventory |
| Selected source unit price | Active marketplace price provenance | Decimal retained as `source_unit_price` | Preserved for all 69,880 active JLCPCB price rows |
| Public sale price | Active marketplace price | `source_unit_price × 1.05` | Exact for all 69,880 active JLCPCB price rows; do not recalculate from another break |
| Cost price | Existing product/price cost field | Existing stored decimal | Equals source unit price for only 2,618 rows; preserve until an approved provenance repair |
| Warehouse quantity | Existing `inventory_stocks` | Existing stock rows only | JLCPCB sum is 712,794; no `stock_type`/allocation policy, so never fabricate 10,000 units |
| Source checksum | `catalog_import_batches.checksum`, product metadata | SHA-256 | New batch/run evidence |
| Transformed payload | `catalog_product_sources.source_payload_hash` | Stable JSON SHA-256 | Updated on source-link refresh |
| Raw transformed part | `catalog_product_sources.raw_snapshot` | JSONB | Preserves source-scoped audit evidence |
| Data quality score | `catalog_product_sources.data_quality_score` | Decimal | Updated without auto-approval |
| Review status | Source link and product metadata | Review state string | Existing approved/rejected decisions are preserved |

## Required provenance-field coverage

| Required field | Existing location/evidence | Coverage status |
|---|---|---|
| `source_name` | `products.attributes.source_metadata`; source catalog row | Present in transformed metadata |
| `source_url` | `catalog_sources`, source link, product import metadata | Present, generally repository-level rather than a unique source-part page |
| `source_file` | `products.attributes.source_metadata` | Present as `jlcpcb-components.sqlite3`; raw file currently absent |
| `source_page_url` | `products.attributes.source_metadata` | Present as the CDFER publication page, not a per-product page |
| `downloaded_at` | `products.attributes.source_metadata` | Known gap: stored as null by the transformer |
| `imported_at` | Source link and metadata | Present |
| `data_year` | `products.attributes.source_metadata` | Present but derived from import time; upstream data year is not independently proven |
| `license_note` | `catalog_sources.license_notes`; source metadata | Present at repository/dataset level; field/media-specific rights are not independently verified |
| `confidence_level` | Source metadata and SEO advisory metadata | Present as source-provided/source-imported |
| `original_raw_value` | Raw snapshot and attribute `raw_value` | Partial: evidence exists, but the exact required dedicated field is not consistently materialized |
| `normalized_value` | Normalized attribute payload/spec value | Present for successfully normalized attributes; not universal |

## AI/SEO advisory coverage

Generated JLCPCB product SEO metadata includes `source_notes`, `confidence_level`, `last_updated` and `Advisory only`. Imported products were created draft/hidden/pending review and noindex until approved. This metadata does not replace source verification or human catalog review.

Fresh review counts are 69,837 pending product approvals and 69,837 unapproved source reviews: 68,813 `source_imported_pending_approval` plus 1,024 `pending_review`. The shared publication-gate implementation has passed local PostgreSQL tests but is not deployed, so the mapping report does not claim a live visibility change.

The backup at `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929` completed a successful isolated restore (dump SHA-256 `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`). The temporary restore database was dropped afterward; this does not substitute for the fresh upstream schema inspection still required before a replay.

## Mapping gaps that block a replay

- A fresh SQLite schema inspection and checksum are required before using the mapper again.
- Existing 69,880 source links must be reconciled before any insert/update plan is approved.
- Missing `downloaded_at` and ambiguous `data_year` require an additive provenance repair plan, not invented values.
- Per-field original/normalized value coverage must be reported before claiming full source-rule compliance.
- Existing JLCPCB inventory must remain unchanged unless an independently approved warehouse receipt/allocation with `stock_type` and policy evidence creates movements.
- Preserve the verified source-price-plus-5% sale rows; do not infer or bulk-rewrite cost price for the remaining rows where `cost_price` is not equal to `source_unit_price`.
- Datasheet and media rights require separate verification.
