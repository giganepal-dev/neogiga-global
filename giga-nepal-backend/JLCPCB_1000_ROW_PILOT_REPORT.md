# JLCPCB 1,000 Row Pilot Report

Status: completed on 2026-07-10.

The controlled production pilot was capped at 1,000 rows by the CLI. No full, 10k, or 100k import was run.

## Successful pilot

- Import batch id: `90f52e20-c1df-41a1-bbd2-19b17f9529f4`
- Rows read: 1,000
- Products inserted: 1,000
- Products updated: 0
- Brands inserted during successful run: 0
- Categories inserted during successful run: 0
- Source links created: 1,000
- Source links updated: 0
- Offers created: 1,000
- Product documents after pilot: 994
- Skipped rows: 0
- Adapter errors: 0
- Imported product state: `draft`, `hidden`, `pending_review`

## Production counts after pilot + idempotency rerun

- `products`: 1,001
- `product_brands`: 36
- `product_categories`: 262
- `catalog_product_sources`: 1,000
- `catalog_distributor_offers`: 1,000
- `catalog_import_batches`: 4
- `catalog_import_errors`: 2,000

## Stopped failed attempts

Two pilot attempts were stopped before the successful run:

1. Batch `b146b6d9-3f1c-4795-a1d2-a9cbedcba081`: 1,000 skipped due UUID serialization in product metadata.
2. Batch `f07d44ff-29bb-4cab-92c9-51f7fc5d93fa`: 1,000 skipped due Decimal serialization in product metadata.

Those failed attempts created no products, source links, offers, specs, or documents. They did create pending-review brands/categories and source-scoped import-error rows, which were retained as audit evidence.

## Smoke checks

- `https://backend.neogiga.com/health`: 200
- `https://neogiga.com/`: 200
- `https://neogiga.com/products`: 200
- `https://neogiga.com/admin`: 200
