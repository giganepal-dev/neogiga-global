# NeoGiga JLCPCB Restore Procedure

Date: 2026-07-15 (Asia/Kathmandu)

## Scope and safety state

No new JLCPCB/LCSC import was run, so there is no new import delta to roll back. This procedure documents recovery for the existing catalog only. The task-specific PostgreSQL dump was restored successfully into an isolated temporary database and that database was dropped afterward. No production restore or production data write was executed.

Never restore directly over production as the first validation step. Never apply PR #15 migrations as part of recovery. Never delete canonical products merely to remove a source link or distributor offer.

## Recovery-point selection

| Intended recovery state | Candidate artifact | Important limitation |
|---|---|---|
| Task-specific captured platform state | `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929` | Preferred restore-tested task artifact; use only when its capture point is the explicitly approved recovery target |
| Before the first JLCPCB pilot | `/home/neogiga/backups/jlcpcb-import-20260710-110520/neogiga_before_jlcpcb_import.dump` | Restore-tested on 2026-07-15, but this would remove all later platform work if restored wholesale |
| Before the 20k pass | `/home/neogiga/backups/neogiga_pre_jlcpcb_20k_20260711T040105Z.dump` | Repository report does not record a SHA-256; do not use until the actual file is hashed and validated |
| Before the 70k pass | `/home/neogiga/backups/neogiga_pre_jlcpcb_70k_scale_indexed_20260711T052257Z.dump` | Repository report does not record a SHA-256; do not use until validated |
| Later full platform state retaining the JLCPCB catalog | `/home/neogiga/backups/regional-catalog-release-20260714-212602-retry1` | Preferred audited full recovery set for later platform state; restore-point timing still differs from the final live state |

Task-specific verified artifacts:

- PostgreSQL dump: `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`
- Contents list: `35d7633806b517cde2ead578db8b2f9a5adf782230d94a21395d762686e3af44`
- Storage archive: `d68994f2e05568db6a07ee6242d77358ef12bcc09e0c936b257fa98b80a44906`
- Release archive: `5c9b504f3af28cce92e61e16ba06f1f58f03182f334c95c3ca3b91362e903382`
- Environment capture: `6f6523986ca471fdf622a1b32a1591500aa0dc88bf93c47029c4e5256fef29f3`
- Restore-verification output: `c25458301ada6cf630e15b78bfaa6f33bf97df3292f1910dc5240078def36b00`
- Counts TSV: `eeccf06c01fda4f8a71af196b95d3e62fa988c5bdfadba99ffb23fc4c5a3f683`

The isolated restore succeeded and the temporary validation database was dropped after verification.

The separately supplied pre-first-import dump was also read back on 2026-07-15. Its SHA-256 is `d29b61dcbd3302c7b04d95065189b66d4a9f63bab1a20dc827198c92006af870`, its size is 1,441,078 bytes, and its custom archive contains 3,823 TOC entries. A full isolated restore succeeded and the temporary database was dropped. The restored baseline contained 1 product, 177 categories, 0 brands, 0 product images, 0 product documents, 1 warehouse, 1 inventory-stock row and 3 inventory movements. `catalog_sources`, `catalog_product_sources`, `catalog_distributor_offers` and `product_search_documents` did not yet exist. This proves it is a pre-JLCPCB database recovery point, not the missing raw supplier dataset.

Other recorded checksums:

- Restore-tested pre-first-import dump: `d29b61dcbd3302c7b04d95065189b66d4a9f63bab1a20dc827198c92006af870`
- Later full PostgreSQL dump: `221be4c39dade47ec85e729467eef5c58c6771d0e66bee7b07d5206e6e72a009`
- Later full storage archive: `6d919f1174c23a365164d3dc0367ed19b91a960c02e6157cccb37679a86842b4`
- Frozen pre-migration PostgreSQL dump: `f46e3ccc176cc17acba1e97770e7c53ed2b971b28971c2f46bc9f53ff6c9e229`
- Pre-width-fix PostgreSQL dump: `e32bd6f54aa1a27a377c8b6af7c8c0ca5f51d6813ab2e923b046e18301d78dab`

## Mandatory pre-restore gates

1. Obtain explicit restore authorization and define the exact target timestamp/scope.
2. Stop or isolate all application and queue writers only during the approved cutover window.
3. Take a fresh immutable backup of the current database, storage, release, `.env` and service/vhost configuration.
4. Hash every restore artifact and compare it with its recorded checksum. Missing historical checksums are blockers, not optional warnings.
5. Run `pg_restore --list` against the custom-format dump and retain the output.
6. Restore first into a new isolated staging database using credentials supplied outside scripts/logs.
7. Use application code and migrations compatible with the selected recovery point.

Illustrative read-only validation commands:

```bash
sha256sum /path/to/selected.dump
pg_restore --list /path/to/selected.dump > /secure/path/selected.dump.list
```

Illustrative isolated restore pattern:

```bash
createdb neogiga_restore_validation
pg_restore --no-owner --no-acl --dbname neogiga_restore_validation /path/to/selected.dump
```

Database names, users and paths are placeholders. Do not put passwords in command history.

## Staging validation

Before any production switch, verify:

- PostgreSQL restore completed without ignored errors.
- Migration table matches the selected application release.
- Referential integrity for products, brands, categories, source links, offers, specs and documents.
- Exact JLCPCB source-link cardinality expected for the chosen recovery point.
- Duplicate uniqueness on source/source-part, product/source, stable SKU and distributor/SKU.
- Hidden/review/publication states and sitemap/noindex gates.
- Search-document/facet state or a governed rebuild plan.
- Product, price, inventory, movement, image, customer and order totals against the selected baseline.
- Public media count and checksum readback when storage is restored.
- Admin, health, global and regional canaries.

The fresh live reference counts for this task are:

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

The 85,392 value is total product-image rows, not the earlier 79,658 active-image count. Recovery-point-specific counts may differ and must never be forced to match by deleting data. In addition to counts, validate that all 69,880 active JLC marketplace-price rows retain `source_unit_price` and satisfy `sale_price = source_unit_price × 1.05`. Do not force `cost_price` to the source value: only 2,618 rows currently match. JLC inventory totals 712,794 units and has no `stock_type` or allocation-policy field, so restore validation must not invent 10,000-unit balances or an 80/20 warehouse split.

Review-state validation must preserve the current 69,837 pending product approvals and the unapproved source-review split of 68,813 `source_imported_pending_approval` plus 1,024 `pending_review` rows. The centralized publication gate passed local PostgreSQL tests but is not deployed; do not treat its local result as a production cutover canary.

## Source-scoped JLCPCB rollback

The existing ETL supports a dry-run-first batch rollback that removes source links and distributor offers for a selected import batch and marks the batch rolled back. It intentionally does not delete canonical product rows. The documented pilot dry run considered 1,000 source links/products and deleted nothing.

Do not use a single pilot batch rollback as a substitute for restoring or reconciling the multi-batch 20k/70k catalog. First produce a complete batch/source-link impact report and preserve manually curated or shared canonical products.

## Production cutover and abort criteria

Only after staging validation succeeds may an approved maintenance-window restore be considered. Keep the current release and fresh pre-restore backup available for atomic rollback. Abort on checksum mismatch, missing table, migration drift, count discrepancy, broken foreign key, unexpected public exposure, missing media or failed application canary.

After cutover, repeat database counts, source-link reconciliation, queue state, search state, sitemap/robots/canonical checks and browser canaries. Record the exact dump hash, storage hash, release commit and operator approval in a new result report.
