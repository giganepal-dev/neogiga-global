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

## Production validation scope

To be completed after deployment:

- `--connection-check --target neogiga`
- `php artisan migrate --pretend`
- `php artisan migrate --force`
- `--target neogiga --dry-run --limit 1000`
- `--target neogiga --publish --pilot --limit 1000`
- idempotency rerun
- rollback dry-run
