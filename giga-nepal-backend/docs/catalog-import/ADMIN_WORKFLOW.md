# Admin Workflow

The protected API surface is under `/api/v1/admin/catalog-ingestion` and uses the existing fail-closed `admin.token` middleware.

- `GET /sources`: supplier compliance configuration and sync state.
- `POST /sources/{supplier}/audit`: read robots and create/update a pending-manual-review policy record.
- `PATCH /sources/{supplier}`: update policy state. Import cannot be enabled unless status is `approved`.
- `GET /runs`: review counters and failures.
- `GET /review-tasks`: review missing MPNs, category mapping, duplicates, and source-product records.

Publication is intentionally outside this surface and remains governed by the existing product approval controls.
