# JLCPCB Duplicate Report

Date: 2026-07-15 (Asia/Kathmandu)

## Status

No new duplicate scan or import was run because the raw source SQLite is absent and 69,880 source-linked JLCPCB products already exist in production. This report consolidates the retained duplicate evidence and identifies what remains unresolved.

## Identity rules already used

The canonical adapter resolves a source row in this order:

1. Existing `catalog_product_sources` row by source plus source part ID.
2. Existing stable NeoGiga SKU derived from the source part ID.
3. Existing product by brand/manufacturer identity plus normalized MPN.
4. Insert a new hidden/review-pending product only when no canonical identity resolves.

Database uniqueness protects source plus source-part identity, product plus source, distributor plus distributor SKU, and stable product SKU/slug behavior.

## Recorded duplicate/conflict outcomes

| Stage | Duplicate/conflict result | Effect |
|---|---:|---|
| Successful 1,000-row pilot | 0 skipped | 1,000 new products and source links |
| Idempotency rerun | 0 new products | 1,000 existing products/source links/offers updated; counts stayed stable |
| 20,000-row pass | 53 conflicts skipped | Multiple source IDs resolved to an already-linked canonical product; no duplicate product rows were created for those rows |
| 70,000-row pass | 120 conflicts skipped | Final source-linked product count was 69,880, not 70,000 |

The first idempotency attempt exposed a duplicate-slug lookup bug and stopped. Commit `b0fc3e7` changed lookup order to source link, stable SKU, then brand plus normalized MPN; the next rerun inserted zero products and updated the expected 1,000 records.

## Counts that must not be conflated

- `70,000` is the number of source rows read in the scale pass.
- `69,880` is the final unique JLCPCB source-linked product count reported after canonical conflict handling.
- `73,058` is the later platform-wide product count after unrelated additive catalog work.
- Supplier SKU/stock/price-break records are offers; they are not additional canonical products or warehouse inventory.

The fresh live snapshot retained exactly 69,880 JLCPCB source-link rows within 73,058 products. The verified backup is `/home/neogiga/backups/jlcpcb-existing-data-20260714_181929`; its database dump SHA-256 is `a6a49f3bdf2a9b36a73d8254a367b85f133cfe174b230a5ccba2120a722be630`, and the counts TSV SHA-256 is `eeccf06c01fda4f8a71af196b95d3e62fa988c5bdfadba99ffb23fc4c5a3f683`. The dump restored successfully into an isolated temporary database, which was dropped afterward.

## Remaining duplicate risks

- Manufacturer punctuation, spacing and alias variants were not fully resolved.
- Generic/unknown categories and blank source categories remain review work, even when product identity is unique.
- The 120 scale conflicts remain audit evidence; this report does not invent a merge decision for them.
- No fresh comparison exists between a current upstream source file and the existing 69,880 source payload hashes.
- Similar names alone are not a safe merge key.
- Barcode, datasheet and attribute similarity were not approved as automatic merge keys in the existing adapter.

## PR #15 duplicate-catalog risk

PR #15 was rejected for this integration path because it adds a separate `canonical_products` table and parallel seller-offer, regional-inventory, regional-price, image and document relationships without a reconciled backfill from the populated `products` and `catalog_product_sources` tables. Using it would create two catalog identities rather than resolve duplicates in the existing catalog. Its rollback also names tables already used by the current application, creating a data-loss risk.

## Required next duplicate gate

Before any future JLCPCB replay, compare a freshly checksummed source against existing source part IDs, payload hashes, stable SKUs and brand-plus-normalized-MPN matches in read-only mode. Report exact unchanged, changed, new, missing and conflicting sets. No write should occur while any conflict lacks an explicit preserve/merge/reject decision.

The current no-import decision created no new duplicate rows. Pricing and inventory must not be used as merge shortcuts: all active JLCPCB sale prices follow the same 5% rule, while cost coverage is incomplete and inventory lacks `stock_type`/allocation-policy evidence.
