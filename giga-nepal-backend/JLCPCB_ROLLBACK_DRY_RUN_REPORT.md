# JLCPCB Rollback Dry-Run Report

Status: completed on 2026-07-10.

Rollback dry-run command:

```bash
tools/jlcpcb_etl/.venv/bin/python -m tools.jlcpcb_etl.cli \
  --target neogiga \
  --rollback-batch 5e397f0f-e279-4a21-b49f-adeb74f27ee8
```

Dry-run result:

- Batch id: `5e397f0f-e279-4a21-b49f-adeb74f27ee8`
- Source links: 1,000
- Products considered: 1,000
- Dry run: true

No rows were deleted. Real rollback remains intentionally manual because it removes source links/offers only and does not delete product rows.
