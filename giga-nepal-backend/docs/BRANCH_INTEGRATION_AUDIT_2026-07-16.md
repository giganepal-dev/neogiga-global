# Branch Integration Audit - 2026-07-16

## Scope

This audit compares remote branches with `origin/main`, the deployed PCB release baseline. It is a merge-selection record, not a release authorization.

## Disposition

| Branch | Unique commits vs `origin/main` | Disposition | Reason |
| --- | ---: | --- | --- |
| `origin/pcb-usable-portal` | 34 | Selective integration | Contains useful portal UI patterns but also 11 migrations and a divergent controller set. Only additive presentation and route-contract fixes are adopted. |
| `origin/next-phase-panel-execution-d162b` | 11 | Hold for module review | Seven migrations and admin changes need schema-by-schema compatibility review. |
| `origin/feature/jlcpcb-existing-data-import` | 7 | Do not merge | Superseded by governed import and publication work already on `main`. |
| `origin/upgrade/regional-platform-catalog-release` | 7 | Do not merge | Superseded by current regional/catalog release work. |
| `origin/neogiga-catalog-import-system-55525` | 6 | Hold for import review | Import-related changes require provenance, checkpoint, and production backup review. |
| `origin/git-repository-access-e1d13` | 5 | Hold for focused review | Contains migration and routing changes outside the PCB scope. |
| `origin/neogiga-product-page-blueprint-d450e` | 4 | Hold for product/RFQ review | Seven migrations and a broad product/RFQ surface are not safe to merge into production as a bundle. |
| `origin/pcb-platform-integration-6724f` | 2 | Hold | Requires comparison against the newer PCB portal implementation. |

## Integrated Safely

- The PCB home is upgraded as an interactive quote workspace while preserving the existing `/api/v1/quote/calculate` integration and all existing PCB project workflows.
- Missing authenticated routes for project updates, cancellation, and quote approval/rejection are restored without changing controllers, middleware, tables, or existing URLs.
- The PCB header no longer lists regional storefronts; regional marketplace navigation remains in the shared footer.

## Data and Rollback

- This change is presentation and route-registration only. It introduces no migrations, seeders, imports, destructive commands, or data updates.
- Rollback is a normal release rollback to the prior application artifact. PCB project, file, quote, order, activity-log, catalog, and marketplace data remain untouched.
