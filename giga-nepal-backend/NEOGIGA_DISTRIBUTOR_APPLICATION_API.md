# NeoGiga Distributor Application API

## Public

`POST /api/distributor-applications`

Also available as:

`POST /api/v1/distributor-applications`

Required fields:

- `business_name`
- `contact_person`
- `email`
- `phone`
- `distributor_type`

Allowed distributor types:

- `country_distributor`
- `regional_distributor`
- `city_distributor`
- `reseller`
- `service_partner`

Response creates `status: pending` only.

## Admin

All routes require `admin.token`.

- `GET /api/admin/distributor-applications`
- `GET /api/admin/distributor-applications/{application}`
- `PATCH /api/admin/distributor-applications/{application}/status`
- `POST /api/admin/distributor-applications/{application}/convert-to-distributor`

Conversion creates a pending distributor only.
