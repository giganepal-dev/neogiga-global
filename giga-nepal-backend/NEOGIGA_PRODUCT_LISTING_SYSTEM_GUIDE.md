# NeoGiga Product Listing System Guide

Core product table: `products`.

This slice adds missing first-class fields for approval status, visibility status, global SKU, model/GTIN, country of origin, manufacturer/importer, warranty, return policy, certifications, package includes, use cases, tags, search keywords, and generic group linkage.

Public APIs added:

- `GET /api/v1/products/{product}/attributes`
- `GET /api/v1/products/{product}/specs`
- `GET /api/v1/products/{product}/variants`
- `GET /api/v1/products/{product}/datasheets`
- `GET /api/v1/products/{product}/warranty`
- `GET /api/v1/products/{product}/generic-suggestions`
- `GET /api/v1/products/{product}/compatible`
- `GET /api/v1/products/{product}/related`
- `GET /api/v1/products/{product}/accessories`

Public visibility uses existing active/approved product status or explicit approved approval status.
