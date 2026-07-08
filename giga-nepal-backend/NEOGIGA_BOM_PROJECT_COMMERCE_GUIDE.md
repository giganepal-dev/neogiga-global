# NeoGiga BOM Project Commerce API

Date: 2026-07-08

## Public API

- `GET /api/v1/bom/projects`
- `GET /api/v1/bom/projects/{slug}`
- `GET /api/v1/bom/projects/{slug}/items`
- `POST /api/v1/bom/projects/{slug}/price`

## Authenticated Customer API

- `POST /api/v1/bom/projects/{slug}/add-to-cart`
- `POST /api/v1/bom/build-custom`
- `POST /api/v1/bom/user-builds`
- `GET /api/v1/bom/user-builds/{build}`

## Admin API

- `GET /api/v1/admin/bom/projects`
- `POST /api/v1/admin/bom/projects`
- `PATCH /api/v1/admin/bom/projects/{project}`
- `POST /api/v1/admin/bom/projects/{project}/items`
- `PATCH /api/v1/admin/bom/projects/{project}/items/{item}`
- `DELETE /api/v1/admin/bom/projects/{project}/items/{item}`

## Notes

- Pricing is computed server-side from marketplace product prices when available, then product price fallback.
- Public project reads are limited to `is_public = true` and `status = published`.
- Add-to-cart records a BOM conversion placeholder only; checkout/order/payment integration stays separate.
- Custom build output includes source notes, confidence level, last updated, and an advisory-only disclaimer.
