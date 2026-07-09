# NeoGiga Seller Early Access API

## Public

`POST /api/seller-applications`

Also available as:

`POST /api/v1/seller-applications`

Required fields:

- `business_name`
- `contact_person`
- `email`
- `phone`
- `business_type`
- `seller_type`

Optional fields include WhatsApp, location IDs, categories, brands, inventory/store flags, monthly capacity, website, message, and source.

Response creates `status: pending` only.

## Admin

All routes require `admin.token`.

- `GET /api/admin/seller-applications`
- `GET /api/admin/seller-applications/{application}`
- `PATCH /api/admin/seller-applications/{application}/status`
- `POST /api/admin/seller-applications/{application}/convert-to-vendor`

Conversion creates a pending vendor only. It does not approve seller access.
