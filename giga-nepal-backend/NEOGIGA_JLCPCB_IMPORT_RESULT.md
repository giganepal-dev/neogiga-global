# NeoGiga JLCPCB Existing-Data Import Result

Date: 2026-07-15 (Asia/Kathmandu)

## Final outcome

**No new import was run.** NeoGiga already has 69,880 usable JLCPCB source-linked production products, and the raw `jlcpcb-components.sqlite3` source is absent from the audited repository/workspace. Replaying an unverified or newly downloaded dataset without first reconciling those existing links would violate the no-data-loss, duplicate-control and provenance rules.

This was the correct safe outcome, not a failed import.

## Operation result

| Operation | Result |
|---|---:|
| Source rows read for a new import | 0 |
| Products inserted | 0 |
| Products updated | 0 |
| Products deleted | 0 |
| Source links/offers created or changed | 0 |
| Marketplace prices changed | 0 |
| Warehouse stock/movements changed | 0 |
| Images/media changed | 0 |
| Review/publication/SEO state changed | 0 |

No JLCPCB distributor price was newly set as cost, no 5% sale margin was newly applied, and no 10,000-unit warehouse allocation was created by this operation. Fresh read-only validation found that all 69,880 active JLCPCB marketplace-price rows already preserve `source_unit_price` and exactly satisfy `sale_price = source_unit_price × 1.05`. Only 2,618 have `cost_price = source_unit_price`, so no bulk cost rewrite is justified. Existing distributor stock remains external offer data, not NeoGiga warehouse inventory.

A separate additive backend publication-query gate on the working branch applies existing product/source approvals consistently across public consumers. It passed local PostgreSQL tests. It is not a data import, does not justify changing stored review states, and is not deployed.

## Existing data retained

The previously governed import sequence remains the authoritative result:

- Successful 1,000-row pilot and idempotency rerun.
- 20,000-row pass: 18,947 inserts, 1,053 updates and 53 canonical conflicts skipped.
- 70,000-row pass: 49,933 inserts, 20,067 updates and 120 conflicts skipped.
- Final recorded JLCPCB source-linked products: 69,880.
- Final recorded JLCPCB search documents/facets: 69,880 / 489,160.
- JLCPCB product images copied/hotlinked: zero; NeoGiga placeholder only because rights were not verified.

The fresh platform-wide live snapshot is:

| Measure | Count |
|---|---:|
| Products | 73,058 |
| Brands | 469 |
| Categories | 441 |
| Product image rows (total) | 85,392 |
| Product document rows | 68,666 |
| JLCPCB source-link rows | 69,880 |
| Marketplace price rows | 73,056 |
| Search document rows | 73,057 |
| Warehouses | 5 |
| Inventory stock rows | 89,586 |
| Inventory movement rows | 12,707 |
| Orders | 2 |
| Customers | 0 |

These totals include other catalog sources and must not be attributed wholly to JLCPCB. The 85,392 image count is total product-image rows, not the earlier 79,658 active-image count.

JLCPCB inventory totals 712,794 units. The inventory schema has no `stock_type` or allocation-policy field, so it does not support fabricating 10,000 units per product or assigning 80% to a central China warehouse. Product approval remains pending on 69,837 rows; unapproved source reviews comprise 68,813 `source_imported_pending_approval` and 1,024 `pending_review` rows. This operation preserved those states.

## Source and backup status

- Historical source: 1.5 GiB, 616,593 rows, SHA-256 `9334f49b7d730b7ed7e5beb3c0360fe89a3a158605af3c4512a10f850c23c986`.
- Current raw source state: absent; historical checksum cannot validate a future download.
- Task-specific backup: `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929`.
- Task-specific PostgreSQL dump SHA-256: `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`.
- Contents-list/storage/release SHA-256 values: `35d7633806b517cde2ead578db8b2f9a5adf782230d94a21395d762686e3af44` / `d68994f2e05568db6a07ee6242d77358ef12bcc09e0c936b257fa98b80a44906` / `5c9b504f3af28cce92e61e16ba06f1f58f03182f334c95c3ca3b91362e903382`.
- Environment/restore-verification/counts-TSV SHA-256 values: `6f6523986ca471fdf622a1b32a1591500aa0dc88bf93c47029c4e5256fef29f3` / `c25458301ada6cf630e15b78bfaa6f33bf97df3292f1910dc5240078def36b00` / `eeccf06c01fda4f8a71af196b95d3e62fa988c5bdfadba99ffb23fc4c5a3f683`.
- The task-specific PostgreSQL dump restored successfully into an isolated temporary database, which was dropped afterward. No production restore occurred.
- Initial pre-import dump SHA-256: `d29b61dcbd3302c7b04d95065189b66d4a9f63bab1a20dc827198c92006af870`; a 2026-07-15 isolated restore succeeded and confirmed a one-product, 177-category baseline with JLCPCB source/link/offer/search tables absent. The temporary database was dropped.
- Later immutable PostgreSQL/storage backup SHA-256 values: `221be4c39dade47ec85e729467eef5c58c6771d0e66bee7b07d5206e6e72a009` / `6d919f1174c23a365164d3dc0367ed19b91a960c02e6157cccb37679a86842b4`.
- Pre-20k and pre-70k backup paths are documented, but repository reports do not record their checksums; they are blocked from restore use until validated.

## PR #15 disposition

PR #15 was rejected for this operation. The audited ref adds a second `canonical_products` source of truth plus parallel offers, regional inventory/prices and product media/document structures, modifies existing migrations, broadens unrelated email/currency/UI scope, and has rollback table names that can overlap live tables. It provides no reconciled backfill for the 69,880 current source-linked products. Merging it would create split catalog ownership and material rollback/data-loss risk.

The existing `products` plus `catalog_product_sources`/`catalog_distributor_offers` architecture remains the approved integration target.

## Gates still open

1. Download the current official source to private staging and record source URL, file, page URL, download time, data year, license note and checksum.
2. Run schema inspection and a read-only source-to-production reconciliation.
3. Report unchanged, changed, missing, new and conflicting source rows without writes.
4. Complete provenance coverage for download time, source year and original/normalized field evidence.
5. Resolve manufacturer/category and canonical duplicate review.
6. Preserve the verified 5% sale-price relationship and define a separately approved repair policy for cost values that do not equal the source price; do not bulk-rewrite them by inference.
7. Require warehouse receipt/ownership evidence before creating inventory or movements.
8. Verify datasheet/image redistribution rights.
9. Take and read back a new immutable backup before any future apply; the restore-tested task backup does not replace a backup for a later write window.
10. Use the existing review-gated admin workflow and perform post-write database, queue, SEO and browser reconciliation.
11. Deploy the locally PostgreSQL-tested centralized publication-query gate through the approved release path and complete live database/SEO/browser canaries.

Import status: **not run; existing production data preserved; task backup restored successfully in isolation**. The existing sale-price formula is verified. Provenance, cost-policy, inventory-allocation, publication deployment and media gates remain **not complete**.
