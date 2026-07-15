# NeoGiga Pre-JLCPCB Existing-Data Baseline

Date: 2026-07-15 (Asia/Kathmandu)

## Baseline decision

This baseline precedes the proposed existing-data integration pass, not the historical 2026-07-10/11 imports. No new JLCPCB/LCSC import was run because NeoGiga already contains 69,880 usable source-linked JLCPCB products and the raw source SQLite is absent from the audited repository/workspace.

The safe baseline is therefore the existing canonical catalog. No parallel catalog, replacement migration, price rewrite, inventory fabrication or publication change is authorized.

## Existing JLCPCB-specific baseline

| Measure | Audited value | Evidence date/scope |
|---|---:|---|
| JLCPCB source-linked products | 69,880 | Final 70k import report, 2026-07-11 |
| JLCPCB search documents | 69,880 | Final 70k import report |
| JLCPCB search facets | 489,160 | Final 70k import report |
| JLCPCB-linked public products after the 20k pass | 24 | Explicit JLCPCB count in the 20k report |
| Platform public products after the 70k pass | 25 | The 70k report does not restate a source-specific public count |
| Source rows read by the final scale pass | 70,000 | 120 duplicate/conflict rows were skipped |
| JLCPCB source images copied/hotlinked | 0 | NeoGiga placeholder was used because rights were not verified |

These values describe the recorded import state. This task also performed a fresh read-only production count and created a task-specific backup, but did not import or modify production data.

## Current platform baseline after later additive work

The fresh read-only production snapshot for this task recorded:

| Platform measure | Count |
|---|---:|
| Products | 73,058 |
| Brands | 469 |
| Categories | 441 |
| Product image rows (total) | 85,392 |
| Product document rows | 68,666 |
| JLCPCB source-link rows | 69,880 |
| Marketplace prices | 73,056 |
| Search documents | 73,057 |
| Warehouses | 5 |
| Inventory stocks | 89,586 |
| Inventory movements | 12,707 |
| Orders | 2 |
| Customers | 0 |

These are platform totals, not JLCPCB-only totals. The 85,392 image count is the total product-image row count and must not be compared as if it were the earlier 79,658 active-image count. The 31,760,000-unit governed allocation and 3,176 cost-plus-5% prices in the 2026-07-14 audit belong to the separate ElecForest release and must not be attributed to JLCPCB.

## Fresh JLCPCB live-data validation

- All 69,880 active JLCPCB marketplace-price rows retain `source_unit_price` and exactly satisfy `sale_price = source_unit_price × 1.05`.
- `cost_price = source_unit_price` on only 2,618 of those rows. The remaining costs must not be inferred or bulk-rewritten without a separately approved repair policy.
- JLCPCB inventory totals 712,794 units. The inventory schema has no `stock_type` or allocation-policy field, so the data does not support fabricating 10,000 units per product or an 80/20 warehouse split.
- Product approval remains pending on 69,837 rows. Unapproved source reviews comprise 68,813 `source_imported_pending_approval` rows and 1,024 `pending_review` rows.
- The additive centralized publication gate passed local PostgreSQL tests, but it has not been deployed; these counts do not represent a live publication-state change.

## Baseline backup register

| Recovery point | Recorded checksum state |
|---|---|
| `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929` PostgreSQL dump | Verified SHA-256 `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`; isolated restore succeeded and the temporary database was dropped |
| Same task backup, contents list | Verified SHA-256 `35d7633806b517cde2ead578db8b2f9a5adf782230d94a21395d762686e3af44` |
| Same task backup, storage archive | Verified SHA-256 `d68994f2e05568db6a07ee6242d77358ef12bcc09e0c936b257fa98b80a44906` |
| Same task backup, release archive | Verified SHA-256 `5c9b504f3af28cce92e61e16ba06f1f58f03182f334c95c3ca3b91362e903382` |
| Same task backup, environment capture | Verified SHA-256 `6f6523986ca471fdf622a1b32a1591500aa0dc88bf93c47029c4e5256fef29f3` |
| Same task backup, restore verification | Verified SHA-256 `c25458301ada6cf630e15b78bfaa6f33bf97df3292f1910dc5240078def36b00` |
| Same task backup, counts TSV | Verified SHA-256 `eeccf06c01fda4f8a71af196b95d3e62fa988c5bdfadba99ffb23fc4c5a3f683` |
| `/home/neogiga/backups/jlcpcb-import-20260710-110520/neogiga_before_jlcpcb_import.dump` | Verified SHA-256 `d29b61dcbd3302c7b04d95065189b66d4a9f63bab1a20dc827198c92006af870`; isolated restore succeeded and its temporary database was dropped. Baseline: 1 product, 177 categories, 0 brands/images/documents, with JLCPCB source/link/offer/search tables absent |
| `/home/neogiga/backups/neogiga_pre_jlcpcb_20k_20260711T040105Z.dump` | Historical 2.7 MB; SHA-256 not recorded in repository report |
| `/home/neogiga/backups/neogiga_pre_jlcpcb_70k_scale_indexed_20260711T052257Z.dump` | Path recorded; SHA-256 not recorded in repository report |
| `/home/neogiga/backups/regional-catalog-release-20260714-212602-retry1` PostgreSQL dump | Verified SHA-256 `221be4c39dade47ec85e729467eef5c58c6771d0e66bee7b07d5206e6e72a009` |
| Same full backup, storage archive | Verified SHA-256 `6d919f1174c23a365164d3dc0367ed19b91a960c02e6157cccb37679a86842b4` |

A fresh task-specific backup was created, read back and successfully restored into an isolated temporary database before that database was dropped. The historical pre-first-import dump supplied for follow-up was independently hashed and restore-tested in the same isolated manner. No production restore, import or data write was performed. Any future write still requires a new immutable pre-operation backup and readback appropriate to that future operation.

## Baseline preservation assertions

- Existing `products`, product categories, brands, source links, offers, prices, inventory, images, routes and theme remain authoritative.
- JLCPCB supplier stock remains an external offer observation, not warehouse inventory.
- No JLCPCB price was newly assigned as cost and no 5% sale price was newly calculated in this operation.
- Existing stored review/publication status was not changed. A separate additive backend publication-query gate on the working branch passed local PostgreSQL tests and may affect which already-reviewed rows are exposed without rewriting those rows; it still requires deployment and live-canary evidence.
- No source image was activated or licensed by this operation.
- No customer, order, email, regional site or legacy branded-site data was touched.

## PR #15 baseline exclusion

PR #15 was rejected for this path. Its nine-commit diff adds a parallel `canonical_products` architecture and overlapping catalog/inventory/price/media structures, edits previously established migrations, and includes rollback table names that overlap live tables. It offers no reconciled backfill for the 69,880 existing source-linked products. Applying it would invalidate this preservation baseline.

## Unresolved gates

- Fresh source file and checksum.
- Source-to-existing-link reconciliation.
- Per-field provenance completion.
- Canonical conflict and taxonomy/brand review.
- JLCPCB-specific cost/margin approval.
- Warehouse receipt/allocation evidence.
- Media/datasheet rights verification.
- A new pre-write backup and tested staging restore for any future write (the current no-write backup was restored successfully in isolation).
- Deployment and live-canary evidence for the additive centralized publication-query gate.
