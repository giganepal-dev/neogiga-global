# JLCPCB Rollback Dry-Run Report

Status: pending live pilot batch id.

Rollback dry-run command:

```bash
tools/jlcpcb_etl/.venv/bin/python -m tools.jlcpcb_etl.cli \
  --target neogiga \
  --rollback-batch IMPORT_BATCH_ID
```

Expected behavior:

- report affected source links
- report products considered
- do not delete products in dry-run mode
- do not remove curated NeoGiga records
