# Price Resolution

`marketplace_product_prices` is the active regional price overlay. A canonical
product remains global; prices are attached to a marketplace and may also be
scoped to a variant or seller. Existing operator-managed rows are never
overwritten by `CentralPricingService`.

Resolution priority is: approved seller/variant overlay, active marketplace
variant overlay, active marketplace product overlay, then an explicitly
configured base-price conversion. Expired, inactive and currency-incompatible
rows must not be presented as live prices.

`CentralPricingService` calculates a traceable regional price from base cost,
fresh FX, duty, margin and tax, writes a calculation log, and only materializes
a missing regional row. It records price history so historic order snapshots
remain immutable.

The next implementation phase should expose this policy through one
`PriceResolver` facade and route every product, search, cart and checkout price
read through it. That work must include effective dates, B2B quantity tiers and
seller-specific approval checks.

`ProductAvailabilityService` now consumes active marketplace overlays for
product detail, cart and checkout. Its final fallback is the canonical product
price, preserving the API-cart contract; the regional web cart still presents
RFQ when no local overlay exists.
