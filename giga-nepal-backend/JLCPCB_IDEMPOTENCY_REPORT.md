# JLCPCB Idempotency Report

Status: passed on 2026-07-10.

Idempotency rerun command was the same capped 1,000-row pilot command.

## Idempotency batch

- Import batch id: `5e397f0f-e279-4a21-b49f-adeb74f27ee8`
- Rows read: 1,000
- Products inserted: 0
- Products updated: 1,000
- Source links created: 0
- Source links updated: 1,000
- Offers created: 0
- Offers updated: 1,000
- Skipped rows: 0

## Counts

- Products stayed at 1,001 after rerun.
- Source links stayed at 1,000 after rerun.
- Offers stayed at 1,000 after rerun.

The first idempotency attempt exposed a duplicate slug lookup bug and stopped. The adapter was patched to resolve products first by `catalog_product_sources`, then stable SKU, then brand + normalized MPN. The passing rerun above validates that fix.
