# REGIONAL_SELLER_GUIDE

**Status: not built this cycle (Stage 3 per the release order). No seller-marketplace-approval schema exists.**

## What exists (adjacent, pre-existing)
NeoGiga has a live seller/vendor system (`vendors` table, seller onboarding/application flow —
see the `neogiga-adaptation-affiliate-status` history and the Onboarding module), plus admin RBAC
tables from `2026_07_10_033000_create_admin_access_control_tables.php`: `user_country_access`,
`user_seller_access` — these scope *admin users* to countries/sellers, not sellers to marketplaces.

## What does NOT exist
`seller_types`, `seller_marketplace_approvals`, `seller_country_approvals`,
`seller_brand_authorizations`, `seller_manufacturer_authorizations`, `seller_product_offers`,
`seller_price_tiers`, `seller_warranties`, `seller_shipping_rules`,
`seller_payment_settlement_rules`, `seller_performance_metrics` — none of these exist. No vendor
row is currently scoped to a specific marketplace at all.

## Why this is deferred
This cycle's scope was explicitly Stage 1 (marketplace/routing) + Stage 2 foundations (pricing
schema). Seller-per-marketplace approval is Stage 3 in the prompt's own release order, and building
it without real sellers wanting to operate in the 22 new preview markets would be speculative
schema with no data to validate it against.

## Design notes for when this is built
- `seller_marketplace_approvals` should reuse the pattern already established by
  `marketplace_product_prices` (marketplace_id FK + status + date range), not invent a new shape.
- Manufacturer-authorized-local-seller pages (e.g. `/np/manufacturer/{slug}`) depend on both this
  system AND the Stage 4 localization/routing work — they are not buildable in isolation.
