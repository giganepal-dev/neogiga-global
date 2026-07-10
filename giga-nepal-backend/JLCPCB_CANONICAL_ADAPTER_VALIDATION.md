# JLCPCB Canonical Adapter Validation

## Local validation scope

The adapter adds tests for:

- database URL precedence
- Laravel `.env` fallback
- special-character password encoding
- missing password rejection
- redacted DSN output
- invalid driver rejection
- deterministic slug/SKU generation
- stable payload hash
- dry-run no-connect behavior
- data quality score penalty

## Production validation result

- Live ETL tests: 39 passed
- Connection source: `LARAVEL_ENV`
- PostgreSQL connection check: passed
- `php artisan migrate --pretend --force`: additive DDL only
- `php artisan migrate --force`: migration ran
- 1,000-row NeoGiga dry-run: 1,000 transformed, 0 skipped
- 1,000-row pilot: 1,000 loaded, 0 skipped
- Idempotency rerun: 1,000 updated, 0 skipped
- Rollback dry-run: source-scoped, 1,000 source links/products considered
- Queue jobs: 0
- Failed jobs: 0
- Database size after pilot: 58 MB

## Remaining validation before scale

- Inspect sample imported products in the admin UI.
- Decide whether to keep, hide, or merge pending-review brands/categories created by the two failed stopped attempts.
- Add search-index/facet rebuild jobs before 10k scale.
