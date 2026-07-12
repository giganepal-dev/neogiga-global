# GitHub Remote Integration Audit

Date: 2026-07-12

## Scope

Reviewed remote branches from `giganepal-dev/neogiga-global` against the current `pcb-usable-portal` branch and the policy-gated catalogue ingestion implementation.

## Decisions

| Remote branch | Claimed capability | Decision | Reason |
| --- | --- | --- | --- |
| `ai-powered-electronics-catalog-management-9435f` | Supplier importers, AI, supplier API clients | Do not merge | Creates parallel supplier/import/product-resource models; assumes undocumented Adafruit/Waveshare API endpoints; automatically creates categories/brands and downloads images without a rights gate. |
| `neogiga-catalog-import-system-55525` | XML/CSV import, catalog master and staging | Do not merge | Creates a second catalogue master, duplicate `catalog_sources`, staging/import tables, manufacturers, taxonomy, and attributes outside current canonical product/provenance tables. |
| `neogiga-multi-vendor-marketplace-architecture-2b61f` | Multi-country, security, tax/localization | Do not merge | Replaces existing country/currency/marketplace models and adds parallel tenancy/security foundations. |
| `next-phase-panel-execution-d162b` | Warehousing, reservations, payments/admin | Do not merge | Introduces duplicate warehouse schemas, legacy-dated migrations, payment gateway configuration, and an inventory reservation flow without the current regional commerce constraints. |
| `pcb-platform-integration-6724f` | PCB/search/product UX fixes | No direct merge | It is an older divergent patch. Current branch contains later PCB lifecycle, migration hardening, and search work; cherry-picking would require a regression-level comparison. |

## Reusable Concepts, Not Direct Code

- Catalog quality scoring has been adapted to the current `supplier_products` provenance model. It scores only observed fields and records missing-field evidence; it does not approve or publish products.
- CSV/XML parser patterns can be added only after source policy approval and an explicit field map, without adding a parallel staging schema.
- Warehouse reservation ideas require an atomic, marketplace-scoped locking design before consideration.

## Current Repository State

- Remote `pcb-usable-portal`: `59c2bd6`.
- Local branch includes `2fd3dc0 Add compliant supplier catalog ingestion foundation`, which is not yet pushed to GitHub.
- No remote branch was merged, cherry-picked, or deployed as part of this audit.
