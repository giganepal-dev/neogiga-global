# NeoGiga Distributor Panel API

Date: 2026-07-08

Base paths:

- Public: `/api/v1/distributors`
- Authenticated distributor: `/api/v1/distributor`
- Admin: `/api/v1/admin`

## Public

- `POST /api/v1/distributors/apply`

Creates a pending distributor application after the distributor migration is run.

## Distributor Auth

Requires `api.token` and `permission:distributor.access`.

- `GET /api/v1/distributor/dashboard`
- `GET /api/v1/distributor/profile`
- `GET /api/v1/distributor/territories`
- `GET /api/v1/distributor/leads`
- `POST /api/v1/distributor/leads`
- `GET /api/v1/distributor/customers`
- `GET /api/v1/distributor/orders`
- `GET /api/v1/distributor/commissions`
- `GET /api/v1/distributor/payouts`
- `GET /api/v1/distributor/downlines`

## Admin

Requires `admin.token`.

- `GET /api/v1/admin/distributors`
- `GET /api/v1/admin/distributors/{distributor}`
- `POST /api/v1/admin/distributors/{distributor}/approve`
- `POST /api/v1/admin/distributors/{distributor}/reject`
- `POST /api/v1/admin/distributors/{distributor}/suspend`
- `POST /api/v1/admin/distributors/{distributor}/assign-territory`
- `GET /api/v1/admin/distributor-commissions`
- `POST /api/v1/admin/distributor-commissions/{commission}/approve`
- `GET /api/v1/admin/distributor-payouts`
- `POST /api/v1/admin/distributor-payouts/{payout}/mark-paid`

## Safety

- All distributor private data is scoped to the authenticated distributor account.
- Admin approval, rejection, suspension, territory, commission, and payout actions are protected by the existing admin token gate.
- Routes return `503` with a pending-migration message until the distributor migration is approved and run.
- Commission and payout state is server-side only.
