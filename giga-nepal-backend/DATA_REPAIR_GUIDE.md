# Data Repair Guide

Never modify production catalog, stock or price records as a first response to
a display discrepancy. Take a database backup, run a read-only audit, review
the resulting identifiers, then apply a narrowly scoped repair in a
transaction.

## Stock discrepancy procedure

1. Confirm product, variant, warehouse, marketplace and vendor identifiers.
2. Check active warehouse assignment and fulfilment eligibility.
3. Compare `quantity_on_hand`, `quantity_reserved` and
   `quantity_available` with the movement and reservation ledger.
4. Record any correction with `StockMovementService`; do not update totals by
   ad-hoc SQL.

## Price discrepancy procedure

1. Inspect active marketplace and seller price overlays plus effective dates.
2. Confirm currency and marketplace context.
3. Inspect central-pricing calculation and regional-price history if a derived
   price is involved.
4. Correct only the applicable overlay and retain an audit reason.

## Deployment safeguard

Run all audits in dry-run/read-only mode first. No current migration is required
for the 2026-07-13 admin repair; production data must not be changed during that
deployment.
