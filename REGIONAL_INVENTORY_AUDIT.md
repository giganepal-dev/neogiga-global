# Regional Inventory Audit

Generated: 2026-07-13

## Current State

- `inventory_stocks` rows are already used by product listing/detail pages.
- Product detail displays stock by warehouse/country when available.
- Marketplace pricing overlays exist through `marketplace_product_prices` and country prices.
- Checkout payment methods are now validated against enabled payment providers.

## Gaps

- Public UI still falls back to product-level `stock_quantity` in some badges.
- Stock formula must be enforced consistently: physical stock minus reserved stock, allocated stock, and safety stock.
- Quantity tiers and regional delivery estimates need stronger public/admin visibility.

## Next Fixes

1. Centralize sellable availability calculation.
2. Replace remaining product-level stock badges with calculated regional stock.
3. Add inventory completeness audit to admin dashboard.
