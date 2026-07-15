# JLCPCB Existing Data Inventory

Date: 2026-07-15 (Asia/Kathmandu)

## Decision

No new JLCPCB/LCSC import was run. NeoGiga already has a usable, source-linked JLCPCB catalog in production, while the raw `jlcpcb-components.sqlite3` source is absent from the audited repository/workspace. Re-downloading or replaying source data without first reconciling it against the existing catalog would create avoidable duplicate, provenance and inventory risk.

This is a read-only production inventory plus an isolated restore verification. It does not claim that source provenance, cost-price, warehouse-policy, media-rights or catalog-review gates are complete. No production data was written.

## Existing production data

The existing production records were created by the governed 1,000-row, 20,000-row and 70,000-row import sequence documented on 2026-07-10 and 2026-07-11.

| Recorded state | Audited count or result | Scope note |
|---|---:|---|
| JLCPCB source-linked products after the 70k pass | 69,880 | Existing canonical `products` linked through `catalog_product_sources` |
| Total products immediately after the 70k pass | 69,881 | Included the original non-JLCPCB product |
| JLCPCB search documents after the 70k pass | 69,880 | Searchable does not mean publicly indexable |
| Search facets after the 70k pass | 489,160 | Historical JLCPCB import result |
| JLCPCB-linked public products after the 20k pass | 24 | Explicit JLCPCB count in the 20k report |
| Platform public products after the 70k pass | 25 | The 70k report did not separately restate JLCPCB-only public cardinality |
| Current platform products after later additive catalog work | 73,058 | Fresh task-specific live snapshot; platform total, not a JLCPCB-only count |
| Current JLCPCB source links | 69,880 | Fresh task-specific live snapshot |
| Current platform marketplace prices | 73,056 | Platform total |
| Current platform inventory stocks / movements | 89,586 / 12,707 | Platform totals; governed ElecForest allocation must not be attributed to JLCPCB |
| Current platform product image rows | 85,392 | Total rows, not the earlier active-only count |

### Fresh task-specific live snapshot

| Table/domain | Exact count |
|---|---:|
| Products | 73,058 |
| Brands | 469 |
| Categories | 441 |
| Product image rows | 85,392 |
| Product documents | 68,666 |
| JLCPCB source links/rows | 69,880 |
| Marketplace prices | 73,056 |
| Search documents | 73,057 |
| Warehouses | 5 |
| Inventory stocks | 89,586 |
| Inventory movements | 12,707 |
| Orders | 2 |
| Customers | 0 |

All 69,880 active JLCPCB marketplace price rows preserve `source_unit_price` and satisfy the exact formula `sale_price = source_unit_price × 1.05`. Only 2,618 have `cost_price = source_unit_price`; the remaining cost-price values must not be overwritten or invented without an approved provenance repair.

The task-specific JLCPCB inventory sum is only 712,794 units, and the rows do not contain the required `stock_type`/allocation-policy evidence. Supplier stock and price breaks remain source observations. They do not authorize fabricating 10,000 units per product or assigning stock to warehouses.

Review gates also remain material: 69,837 products have pending product approval, and the unapproved source reviews consist of 68,813 `source_imported_pending_approval` plus 1,024 `pending_review` rows. The centralized publication-gate code passed local PostgreSQL tests, but it is not deployed.

## Task-specific verified backup and restore

- Backup: `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929`
- PostgreSQL dump SHA-256: `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`
- Contents-list SHA-256: `35d7633806b517cde2ead578db8b2f9a5adf782230d94a21395d762686e3af44`
- Storage archive SHA-256: `d68994f2e05568db6a07ee6242d77358ef12bcc09e0c936b257fa98b80a44906`
- Release archive SHA-256: `5c9b504f3af28cce92e61e16ba06f1f58f03182f334c95c3ca3b91362e903382`
- Environment file SHA-256: `6f6523986ca471fdf622a1b32a1591500aa0dc88bf93c47029c4e5256fef29f3`
- Restore-verification SHA-256: `c25458301ada6cf630e15b78bfaa6f33bf97df3292f1910dc5240078def36b00`
- Counts TSV SHA-256: `eeccf06c01fda4f8a71af196b95d3e62fa988c5bdfadba99ffb23fc4c5a3f683`

The PostgreSQL dump restored successfully into an isolated temporary database, and the temporary database was dropped. The separately captured live-count artifact is checksum-registered above. Production was not restored or mutated.

## Raw source availability

| Artifact | Current local state | Recorded historical evidence |
|---|---|---|
| `tools/jlcpcb_etl/output/jlcpcb-components.sqlite3` | Absent | Previously reported as 1.5 GiB, 616,593 rows |
| Source checksum sidecar | Absent | Historical SHA-256 `9334f49b7d730b7ed7e5beb3c0360fe89a3a158605af3c4512a10f850c23c986` |
| ETL checkpoint files | None | No resumable local source checkpoint remains |
| ETL virtual environment | Absent | Dependencies can be recreated from `requirements.txt`; this was not done in this audit |
| Product-image source dataset | Absent | The 70k import explicitly used a NeoGiga placeholder because redistribution rights were not verified |

The historical checksum identifies the file that was audited at that time. It cannot authenticate a future download, and it is not evidence that the file is still present.

## Retained import implementation and evidence

- `tools/jlcpcb_etl/`: 18 Python modules, nine pytest files and source/canonical adapters.
- `tools/jlcpcb_etl/mappings/categories.yaml`: 320 lines of category mapping rules.
- `tools/jlcpcb_etl/output/`: 15 retained report/output files, approximately 118 KB without the raw SQLite.
- `tools/jlcpcb_etl/output/unmapped_categories_report.json`: 919 aggregate entries covering 137,070 historically unresolved source rows; it is not a product dataset.
- Thirteen existing `JLCPCB*.md` reports in the backend root document schema, mapping, pilot, idempotency, rollback, 20k, 70k, taxonomy and SEO decisions.
- `database/migrations/2026_07_10_120000_create_jlcpcb_catalog_provenance_tables.php`: additive source/batch/link/error/offer schema.
- `resources/views/admin/jlcpcb-imports.blade.php` and existing controller actions: review, approve, reject, publish and search-rebuild operations.

The current `sqlite_schema_report.json`, `validation_report.json` and `validation_report.md` describe a one-row temporary pytest fixture. They must not be treated as the real 616,593-row source audit.

## Git evidence and PR #15

The governed ETL and import history is already present on `main` and the current feature branch. Relevant milestones include `d34bcf2` (canonical safety gate), `f8f02d1` (1,000-row pilot results), `19dc132` (20k guarded import) and `5d6e08a` (70k scale import).

PR #15 (`origin/pr-15`, audited tip `a0d960e`) was not selected for this integration. It creates a parallel `canonical_products` catalog and overlapping offer, inventory, price, image and document structures instead of reusing the populated canonical tables. It also edits already-established migration files and contains rollback methods that can drop table names already used by the live application. Those properties conflict with the upgrade-only/no-data-loss rule and do not provide a reconciled migration of the 69,880 existing source-linked products.

## Remaining gates

- Obtain a fresh official source file, record download time/license/version and verify a new checksum.
- Reconcile every source part ID and payload hash against the existing 69,880 source links before considering writes.
- Resolve incomplete per-field provenance, including null download time and source-year ambiguity.
- Review canonical duplicate conflicts and pending brand/category normalization.
- Keep distributor stock separate from warehouse inventory until an approved ownership/allocation policy exists.
- Preserve the verified `source_unit_price × 1.05` sale prices; do not bulk-rewrite `cost_price` without provenance.
- Keep imported pages and media behind their existing review and rights gates until independently verified and deploy the locally tested publication gate only after production canaries.
