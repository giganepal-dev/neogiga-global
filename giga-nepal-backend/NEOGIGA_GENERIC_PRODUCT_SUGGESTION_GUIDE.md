# NeoGiga Generic Product Suggestion Guide

Tables added:

- `product_generic_groups`
- `product_generic_suggestions`

Public APIs:

- `GET /api/v1/products/{product}/generic-suggestions`
- `GET /api/v1/products/{product}/related`
- `GET /api/v1/products/{product}/compatible`
- `GET /api/v1/products/{product}/accessories`

Suggestions include type, priority, reason, marketplace/category scope, and active status. Existing `product_related_items` is used as fallback when direct generic suggestions are missing.
