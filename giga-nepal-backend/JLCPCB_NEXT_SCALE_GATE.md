# JLCPCB Next Scale Gate

Do not scale beyond 1,000 rows until all gates are complete.

## Required before 10,000 rows

- 1,000-row pilot has zero adapter errors
- idempotency rerun shows no duplicate products/specs/datasheets
- rollback dry-run is source-scoped and safe
- search/product/admin smoke tests pass
- queue remains healthy
- disk and database growth are reviewed
- manually inspect sample imported products in admin

## Still forbidden in current execution

- full import
- 10,000-row import
- 100,000-row import
- publishing thin imported pages publicly
