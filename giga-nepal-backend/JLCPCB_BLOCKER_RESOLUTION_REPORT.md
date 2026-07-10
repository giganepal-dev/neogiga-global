# JLCPCB Blocker Resolution Report

Date: 2026-07-10

## Blockers addressed in source control

| Blocker | Resolution |
| --- | --- |
| `DATABASE_URL` missing in production shell | Added safe resolver that reads `DATABASE_URL` first, then Laravel `.env` DB settings, without logging credentials. |
| Old write path targets standalone ETL tables | Added explicit target adapter path; standalone remains available, NeoGiga canonical is selected by `--target neogiga`. |
| No canonical NeoGiga mapping | Added `NeoGigaCanonicalAdapter` for existing brands, categories, products, specs, documents, and source-scoped offers. |
| Provenance missing | Added additive `catalog_*` provenance migration with source, batch, product source links, import errors, and distributor offers. |
| Live directory not a Git repo | Changes are made in the local Git repo and are intended for additive deploy to live release. |

## Write guardrails

- NeoGiga writes require `--target neogiga --publish --pilot`.
- NeoGiga pilot writes require `--limit <= 1000`.
- Rollback defaults to dry-run unless `--publish` is passed.
- Connection source printing never prints the DSN.
