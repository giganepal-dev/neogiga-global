# FREIGHT_AND_ETA_GUIDE

**Status: not built this cycle (Stage 3). Only a minimal subtotal-based shipping estimate exists.**

## What exists
`RegionalCommerceService::shippingEstimate(float $subtotal, ?object $deliveryZone): float` —
a simple subtotal-driven estimate, already wired into `applyCartEstimates()`. No carrier, no
weight/volumetric calculation, no rate card.

## What does NOT exist
`carriers`, `carrier_services`, `carrier_marketplaces`, `shipping_zones`, `freight_rate_cards`,
`volumetric_weight_rules`, `dangerous_goods_rules`, `battery_shipping_rules`,
`remote_area_surcharges`, `customs_brokerage_rules`, `transit_time_rules`, `delivery_promises`,
`shipment_quotes`, `shipment_quote_items` — none of these exist.

## Critical constraint carried forward
The original spec is explicit: **"Never use one permanent delivery claim globally"** — labels like
"Same-Day Delivery" must only render when address+stock actually qualify. Nothing in NeoGiga today
generates delivery-time copy at all (the existing `shippingEstimate()` returns a cost, not a
promise), so there is currently no risk of an unsupported claim — but this constraint must be
respected from the first line of code once delivery-promise UI is built, not retrofitted later.

## Why this is deferred
Stage 3 per the release order; also blocked on the warehouse-network gap (see
GLOBAL_WAREHOUSE_ROUTING_GUIDE.md) — freight quoting needs real origin warehouses to quote from.
