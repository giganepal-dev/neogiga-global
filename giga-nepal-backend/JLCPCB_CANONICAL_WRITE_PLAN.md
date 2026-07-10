# JLCPCB Canonical Write Plan

## Gate order

1. Verify production backup exists.
2. Resolve PostgreSQL connection from `DATABASE_URL` or Laravel `.env` without printing credentials.
3. Run unit tests.
4. Run SQLite inspect.
5. Run 1,000-row dry-run with `--target neogiga`.
6. Run Laravel `migrate --pretend`.
7. Run additive provenance migration.
8. Run one controlled publish pilot: `--target neogiga --publish --pilot --limit 1000`.
9. Run the same pilot again to prove idempotency.
10. Run rollback dry-run only.
11. Smoke test public/admin endpoints.

## Production write command

```bash
tools/jlcpcb_etl/.venv/bin/python -m tools.jlcpcb_etl.cli \
  --target neogiga \
  --publish \
  --pilot \
  --limit 1000 \
  --batch-size 5000 \
  --sqlite-file tools/jlcpcb_etl/output/jlcpcb-components.sqlite3
```

## Explicit stop conditions

- Connection cannot resolve from safe source.
- Tests fail.
- Migration pretend shows destructive changes.
- Dry-run skips rows unexpectedly.
- Pilot errors are non-zero.
- Idempotency rerun creates duplicate products/specs/datasheets.
- Health/admin/product smoke tests fail.

Full import, 10k import, and 100k import are out of scope for this execution.
