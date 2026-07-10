# GLOBAL_WAREHOUSE_ROUTING_GUIDE

**Status: not built this cycle (Stage 3). A real but minimal warehouse network already exists.**

## What exists (pre-existing)
`warehouses` table (marketplace_id, vendor_id, name, code, country_id/region_id/city_id,
address, contact info, is_active, is_default, operating_hours, metadata) — **1 row live in
production** as of the JLCPCB investigation earlier this cycle. `RegionalCommerceService::
bestWarehouseRoute()` already exists and is called from `applyCartEstimates()` — a working,
if currently trivial (single-warehouse), routing function.

Note: the panel-execution branch reviewed earlier this cycle (`next-phase-panel-execution-d162b`,
see PANEL_BRANCH_REVIEW.md) contains an **incompatible parallel redesign** of `warehouses` (UUID
keys, denormalized region strings) — explicitly flagged DO NOT MERGE. Any future warehouse-network
expansion must extend the live bigint-keyed table, not that branch's schema.

## What does NOT exist
`warehouse_marketplaces`, `warehouse_service_regions`, `warehouse_inventory_lots`,
`warehouse_stock_reservations` (note: `cart_reservations`/inventory-reservation work exists in the
same panel branch under review — see PANEL_BRANCH_REVIEW.md cluster 2, marked mergeable),
`warehouse_transfers`, `warehouse_fulfillment_rules`, `supplier_virtual_stock`,
`inventory_freshness_logs`, `inventory_route_scores`, or a `NearestFulfillmentService`.

## Why this is deferred
Stage 3 per the prompt's release order, and genuinely blocked on having more than one real
warehouse to route between — building a multi-warehouse routing algorithm against a single-row
table would be untestable in any meaningful way.

## Next step
Once real regional warehouses exist, extend `bestWarehouseRoute()` rather than replacing it —
it already has the right call signature (`productId, quantity, marketplaceId, countryId`) to grow
into the full `NearestFulfillmentService` the spec describes.
