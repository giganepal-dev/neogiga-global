# NeoGiga B2B Commerce API

Date: 2026-07-08

## Public

- `POST /api/v1/b2b/apply`

## B2B Buyer

Requires `api.token` and `permission:b2b.access`.

- `GET /api/v1/b2b/account`
- `PATCH /api/v1/b2b/account`
- `POST /api/v1/b2b/rfq`
- `GET /api/v1/b2b/rfq`
- `GET /api/v1/b2b/quotations`
- `POST /api/v1/b2b/quotations/{quotation}/accept`

## Admin

Requires `admin.token`.

- `GET /api/v1/admin/b2b/accounts`
- `GET /api/v1/admin/b2b/accounts/{account}`
- `POST /api/v1/admin/b2b/accounts/{account}/approve`
- `POST /api/v1/admin/b2b/accounts/{account}/reject`
- `GET /api/v1/admin/b2b/rfq`
- `GET /api/v1/admin/b2b/rfq/{rfq}`
- `POST /api/v1/admin/b2b/rfq/{rfq}/create-quotation`
- `GET /api/v1/admin/b2b/quotations`
- `POST /api/v1/admin/b2b/quotations`
- `GET /api/v1/admin/b2b/purchase-orders`
- `GET /api/v1/admin/b2b/price-lists`

## Safety

- B2B users are scoped to their own account server-side.
- Quotation totals are calculated server-side.
- Accepted quotations preserve price snapshots.
- Routes return `503` until the B2B migration is approved and run.
