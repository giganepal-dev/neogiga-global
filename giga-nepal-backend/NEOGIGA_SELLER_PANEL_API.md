# NeoGiga Seller Panel API

Date: 2026-07-07

Base path: `/api/v1/seller`

Auth:

- `Authorization: Bearer <api_token>`
- User role must allow `seller.access`.

## Dashboard

- `GET /dashboard`
- `GET /dashboard/overview`
- `GET /dashboard/sales-summary`
- `GET /dashboard/order-summary`
- `GET /dashboard/product-summary`
- `GET /dashboard/inventory-summary`
- `GET /dashboard/payout-summary`
- `GET /dashboard/alerts`

## Profile

- `GET /profile`
- `PATCH /profile`
- `GET /marketplace-approvals`
- `POST /marketplace-approvals`

## Products

- `GET /products`
- `POST /products`
- `GET /products/{product}`
- `PATCH /products/{product}`
- `POST /products/{product}/submit-review`

## Inventory

- `GET /inventory`
- `POST /inventory/adjust`

## Orders

- `GET /orders`
- `GET /orders/{order}`
- `PATCH /orders/{order}/status`

## Payouts and Performance

- `GET /payouts`
- `GET /performance`

## Support

- `GET /support-tickets`
- `POST /support-tickets`

## Security Rules

- Seller routes are authenticated and permission-protected.
- Seller data is scoped server-side to the vendor linked to the authenticated account.
- Seller cannot access another vendor's products, orders, inventory, payouts, or support tickets.
- Product, inventory, order, and payout totals are calculated and stored server-side.
- Newly added table-backed endpoints return `503` until the Phase B migration is run.
