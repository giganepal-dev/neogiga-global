# NeoGiga Post-JLCPCB Integrity Report

Date: 2026-07-15 (Asia/Kathmandu)

## Result

No new JLCPCB/LCSC import was run. The existing 69,880 source-linked production products were preserved because they are usable and the raw source SQLite is absent from the audited workspace. Consequently, this operation has no import delta: zero products, source links, offers, prices, stocks, movements, images or publication states were intentionally inserted, updated or deleted.

This report includes a fresh read-only production count and a successful isolated restore of the task-specific backup. It does not claim a fresh browser session or any production restore/import/data write on 2026-07-15.

## Preserved audited baseline

| Integrity measure | Fresh live production value | This no-import operation |
|---|---:|---:|
| Products | 73,058 | 0 writes |
| Brands | 469 | 0 writes |
| Categories | 441 | 0 writes |
| Product image rows (total) | 85,392 | 0 writes |
| Product document rows | 68,666 | 0 writes |
| JLCPCB source-link rows | 69,880 | 0 writes |
| Marketplace prices | 73,056 | 0 writes |
| Search documents | 73,057 | 0 writes |
| Warehouses | 5 | 0 writes |
| Inventory stocks | 89,586 | 0 writes |
| Inventory movements | 12,707 | 0 writes |
| Orders | 2 | 0 writes |
| Customers | 0 | 0 writes |

The platform totals include later non-JLCPCB catalog work. The 85,392 value counts all product-image rows and is distinct from the earlier 79,658 active-image figure. Platform totals must not be used to infer a JLCPCB-specific warehouse allocation or price calculation.

## Fresh JLCPCB integrity checks

- All 69,880 active JLCPCB marketplace-price rows preserve `source_unit_price` and exactly satisfy `sale_price = source_unit_price × 1.05`.
- Only 2,618 of those rows have `cost_price = source_unit_price`; no bulk cost repair or rewrite was performed.
- JLCPCB inventory totals 712,794 units. Inventory lacks `stock_type` and an allocation-policy field, so no 10,000-unit-per-product or 80/20 warehouse allocation is evidenced.
- Product approval is pending for 69,837 rows. Unapproved source reviews comprise 68,813 `source_imported_pending_approval` rows and 1,024 `pending_review` rows.
- The centralized publication gate passed local PostgreSQL tests but remains undeployed.

## Integrity boundaries retained

- Canonical records remain in existing `products`, not a new parallel catalog.
- JLCPCB identity remains in `catalog_product_sources` with source-scoped batches and payload hashes.
- Distributor observations remain in `catalog_distributor_offers`.
- Supplier stock was not converted into warehouse inventory.
- No JLCPCB distributor price was newly treated as cost or marked up by 5%.
- No source image was copied, hotlinked, licensed or activated.
- Existing draft/hidden/review/indexability database values were not changed. A separate working-branch publication-query gate passed local PostgreSQL tests and is intended to enforce those existing values consistently; it is not represented here as a database import and is not deployed.
- Existing customers, orders, email configuration, regional sites and legacy branded sites were untouched.

## Backup and restore integrity

The task-specific backup is `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929`. Its verified artifacts are:

- PostgreSQL SHA-256: `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`
- Contents-list SHA-256: `35d7633806b517cde2ead578db8b2f9a5adf782230d94a21395d762686e3af44`
- Storage-archive SHA-256: `d68994f2e05568db6a07ee6242d77358ef12bcc09e0c936b257fa98b80a44906`
- Release-archive SHA-256: `5c9b504f3af28cce92e61e16ba06f1f58f03182f334c95c3ca3b91362e903382`
- Environment-capture SHA-256: `6f6523986ca471fdf622a1b32a1591500aa0dc88bf93c47029c4e5256fef29f3`
- Restore-verification SHA-256: `c25458301ada6cf630e15b78bfaa6f33bf97df3292f1910dc5240078def36b00`
- Counts-TSV SHA-256: `eeccf06c01fda4f8a71af196b95d3e62fa988c5bdfadba99ffb23fc4c5a3f683`

The PostgreSQL dump restored successfully into an isolated temporary database, which was dropped afterward. No production restore was performed.

The later immutable recovery set containing the retained JLCPCB catalog also records:

- PostgreSQL SHA-256: `221be4c39dade47ec85e729467eef5c58c6771d0e66bee7b07d5206e6e72a009`
- Storage archive SHA-256: `6d919f1174c23a365164d3dc0367ed19b91a960c02e6157cccb37679a86842b4`

The initial pre-JLCPCB dump checksum is `d29b61dcbd3302c7b04d95065189b66d4a9f63bab1a20dc827198c92006af870`. Checksums are not recorded in repository reports for the pre-20k and pre-70k dumps; those artifacts remain unusable until independently hashed and validated.

## Evidence that is not a current source audit

- The historical 1.5 GiB source checksum is retained, but the file itself is absent.
- Current ETL validation JSON/Markdown describes a one-row temporary pytest fixture.
- Historical full-source quality metrics and 70k import counts remain useful audit evidence but are not a fresh replay result.
- No fresh upstream-to-production missing/changed-row comparison exists.

## Outstanding integrity gates

- Fresh upstream source checksum and download/license metadata.
- Read-only reconciliation of current source rows to the 69,880 links and 120 recorded conflicts.
- Per-field provenance completion, especially download time, data year and original/normalized value coverage.
- A repair/approval policy for JLCPCB costs that do not equal the preserved source price, plus warehouse receipt/allocation evidence.
- Taxonomy, manufacturer and canonical duplicate review.
- Datasheet/image rights verification.
- Deployment and live-canary evidence for the locally PostgreSQL-tested centralized publication-query gate before claiming a live visibility change.

Integrity status for this task: **preserved by no import/data-write operation, with a successful isolated backup restore**. The existing 5% sale-price relationship is verified; import completeness, provenance completeness, cost-policy completeness and inventory readiness remain **not proven**.
