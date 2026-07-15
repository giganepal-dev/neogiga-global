# JLCPCB Data Quality Report

Date: 2026-07-15 (Asia/Kathmandu)

## Outcome

No new import or raw-source scan was run. Existing production data was retained because NeoGiga already has 69,880 JLCPCB source-linked products and the audited workspace does not contain the raw SQLite source. The source-file figures below are historical and are not represented as a fresh 2026-07-15 upstream validation; the separately labeled live-data checks are fresh read-only production results.

## Historical source audit

The retained implementation report records the following for the previously downloaded CDFER JLCPCB/LCSC SQLite file:

| Metric | Historical result |
|---|---:|
| Source file size | 1.5 GiB |
| Source rows | 616,593 |
| Full dry-run rows transformed | 616,593 |
| Full dry-run rows skipped | 0 |
| Rows mapped to Uncategorized/Needs Review | 137,070 |
| Rows with a blank source category | 100,942 |
| Rows without a datasheet | 140,221 |
| Rows without a package | 0 |
| Aggregate unresolved-category entries retained locally | 919 |

The source file is now absent locally. Its historical SHA-256 was `9334f49b7d730b7ed7e5beb3c0360fe89a3a158605af3c4512a10f850c23c986`; that value must not be reused as proof for a future download.

## Import-stage quality outcomes

| Stage | Rows read | Inserted | Updated | Conflict/skipped | Quality note |
|---|---:|---:|---:|---:|---|
| Successful pilot | 1,000 | 1,000 | 0 | 0 | Draft, hidden and pending review |
| Pilot idempotency rerun | 1,000 | 0 | 1,000 | 0 | Product/source-link/offer counts remained stable |
| 20k pass | 20,000 | 18,947 | 1,053 | 53 | Canonical duplicate conflicts were retained in import errors |
| 70k pass | 70,000 | 49,933 | 20,067 | 120 | Finished with 69,880 unique source-linked products |

The first two stopped pilot attempts created no products, source links, offers, specifications or documents. They did retain import-error evidence and pending-review taxonomy records. The successful idempotency rerun fixed a duplicate-slug lookup issue by resolving source link, stable SKU, then brand plus normalized MPN.

## Fresh live-data quality verification

The task-specific read-only production snapshot confirmed 69,880 JLCPCB source links within a 73,058-product catalog. The source-linked records coexist with 469 brands, 441 categories, 85,392 image rows, 68,666 product documents and 73,057 search documents. Separately, the task-specific dump completed a successful isolated restore.

| Current JLCPCB quality check | Verified result |
|---|---:|
| Active price rows checked | 69,880 |
| Rows preserving `source_unit_price` and exact `sale_price = source_unit_price × 1.05` | 69,880 |
| Rows where `cost_price = source_unit_price` | 2,618 |
| JLCPCB inventory-unit sum | 712,794 |
| Products with pending product approval | 69,837 |
| Source reviews `source_imported_pending_approval` | 68,813 |
| Source reviews `pending_review` | 1,024 |

The sale-price formula is therefore verified for all active JLCPCB price rows. Cost price is not complete enough for a bulk rewrite. Inventory lacks `stock_type` and an allocation-policy field, so the 712,794-unit observation cannot be replaced with an invented 10,000 units per product.

The snapshot was protected by `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929`. Its PostgreSQL dump SHA-256 is `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`; an isolated restore succeeded and the temporary database was dropped. The live counts are preserved separately in the checksum-registered counts TSV.

## Field quality

### Sufficient for source identity

- Stable LCSC/JLCPCB source part ID.
- Manufacturer text normalized to a brand identity.
- MPN, with a stable `LCSC-{source_part_id}` fallback when absent.
- Deterministic NeoGiga SKU for new products.
- Source payload hash and raw snapshot.
- Source-scoped distributor SKU/offer identity.

### Known limitations

- Large unresolved/blank taxonomy population remains documented.
- Manufacturer punctuation/spacing variants still require human normalization review.
- Historical source download time was not persisted in transformed `source_metadata` (`downloaded_at` is null).
- `data_year` was generated from import time and is not proven to be the upstream dataset publication year.
- Raw and normalized attribute values are present in attribute payloads, but the exact required `original_raw_value` field name is not consistently materialized as a dedicated database field.
- Source-level license notes describe the repository as MIT; they do not independently establish rights for every upstream component field, datasheet or image.
- Supplier stock is an external observation, not verified NeoGiga warehouse ownership or allocation policy.
- The active JLCPCB sale-price formula is exact and source price is preserved, but only 2,618 cost-price values equal the source value; missing/different cost values must not be rewritten without provenance.
- The 70k pass used a NeoGiga placeholder; no JLCPCB/LCSC product-image rights were established.

## Current confidence

| Area | Confidence | Reason |
|---|---|---|
| Source-row identity | High for the retained links | Unique source/source-part constraints and idempotency evidence |
| Canonical product match | Medium | Deterministic rules exist, but 120 final conflicts and taxonomy/brand review remain |
| Descriptions/specifications | Source-provided | Not independently manufacturer-verified in this audit |
| Sale-price formula | High for active JLCPCB rows | All 69,880 equal preserved source unit price × 1.05 |
| Cost price | Low/incomplete | Only 2,618 equal the preserved source unit price; no bulk rewrite is authorized |
| Inventory allocation | Advisory only | 712,794 observed units lack `stock_type` and allocation-policy evidence |
| Taxonomy | Mixed | 137,070 historical rows required Uncategorized/Needs Review handling |
| Media | Not verified | Placeholder-only JLCPCB import; redistribution rights unresolved |

The centralized publication gate passed local PostgreSQL tests against approval/source-review cases. It is not deployed, so production visibility protection from that new code is not yet claimed.

All recommendations are advisory only. Confidence reflects the retained audit evidence and must be updated after a fresh source checksum, source-to-production reconciliation and manual review.
