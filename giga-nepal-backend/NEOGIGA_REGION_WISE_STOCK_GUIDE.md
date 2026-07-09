# NeoGiga Region-Wise Stock Guide

Core tables:

- `inventory_stocks`
- `inventory_movements`
- `warehouses`
- `regional_inventory_visibility`
- `marketplace_inventory_visibility`
- `low_stock_alerts`

Public stock APIs:

- `GET /api/v1/products/{product}/stock`
- `GET /api/v1/products/{product}/stock/marketplace/{marketplace}`
- `GET /api/v1/products/{product}/stock/region/{region}`

Public responses expose simplified stock status and quantities only. Warehouse address/contact details are not returned.
