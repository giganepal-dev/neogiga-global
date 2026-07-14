# 6 — Seller Feature Gap Report (2026-07-12)

NeoGiga today: `vendors` + seller-application API (SellerApplicationApiTest), B2B account isolation
(`B2BContextService`, `b2b_account_id`) on RFQ/quotes, wallet module, commission fields in the
affiliate ledger idiom — but **no seller-facing portal UI at all**. Sellers currently interact only
via admin-managed records. Reference source for the portal IA: reference Seller/* controllers.

| Capability (mission) | State | Recommended implementation / notes |
|---|---|---|
| Seller dashboard (revenue/orders/products/stock/returns/earnings/settlement/top products/RFQs/reviews/performance) | MISSING (UI) / PARTIAL (data) | Data must be derived from `order_seller_splits`, not a single `orders.seller_id`. Split rows must carry item subtotal, discount, tax, shipping, payment capture/refund, fulfillment/warehouse, and settlement liability allocations before dashboard money KPIs ship. Build `/seller` Blade portal gated by a new `seller.web` session guard reusing session mechanics + seller-scope middleware. P1-R2 |
| Seller-only-sees-own-data isolation | PARTIAL | B2B isolation proven on RFQ/quotes; must be generalized to reads and writes. Every seller query and mutation for products, offers, inventory, shops, order status, refunds, settlements, and messages must be scoped by authenticated seller/vendor context. Never trust client-submitted `vendor_id`; derive it server-side, reject mismatches, authorize existing records before update/delete, and ship **seller-isolation tests**. P0 for portal |
| Add product / request global MPN match | PARTIAL | products have `vendor_id`+`created_by`+approval statuses (draft/pending/approved/rejected) — the moderation flow exists; seller UI missing. "Request global match" = reuse `BomComponentMatcher` normalized-MPN lookup. Guard: sellers must NOT edit canonical brand/MPN (enforce in policy) |
| Seller offer vs global product | MISSING (model) | needs `seller_offers` (product_id, vendor_id, price, stock, MOQ, tiers) — aligns with reseller-pricing engine scopes already in PricingRuleResolver |
| Variations / SKU / barcode | PARTIAL | variants exist; barcode generation absent (milon/barcode, R2) |
| MOQ / quantity tiers | PARTIAL | pricing engine supports quantity_tier scope — surface it in seller offer UI |
| Warehouse stock | EXISTING (schema) | `inventory_stocks` w/ warehouse+marketplace; seller-scoped write API missing. The write API must derive seller/vendor scope from `seller.web` and ignore/reject client-supplied vendor scope |
| Bulk import/export (seller) | MISSING | reuse the Release-1 generic importer with vendor scope |
| Seller order list/accept/pack/dispatch/tracking | MISSING | requires order seller-split (`order_seller_splits`) — the single biggest schema addition for R2 |
| Seller invoice / refund / return handling | MISSING | builds on admin refund workflow (R1) with seller liability field |
| Internal messages / customer communication via NeoGiga only | PARTIAL | support-ticket message model exists; extend to conversations (chat report in R3). Mission rule: no public phone/email/URLs — enforce in shop publication validation |
| Shop management (name/permalink/logo/banner/vacation/bank-private/publication approval) | MISSING | `seller_shops` table + moderated publication; SEO draft via existing MarketplaceSeoService idiom |
| Settlement / payout / withdraw | MISSING | `seller_settlements` + `payout_requests` ledger; wallet module + affiliate `CommissionLedgerEntry` are the in-house patterns to reuse |
| Performance score | MISSING | derived metric (fulfilment rate, ratings, disputes) — last in R2 |

**Order:** seller-split schema → seller guard + isolation tests → portal dashboard/products/orders →
shop management → settlements → barcode/POS (see POS report). Everything gated on Release 1's RBAC
scopes (seller scope) landing first.

## Seller ownership contract

`order_seller_splits` is the source of truth for seller attribution. Seller dashboards, order lists,
settlements, refunds, returns, commission liability, fulfillment status, and top-product metrics
must read from split-level allocations. Do not implement seller financial reporting from
`orders.seller_id`; it cannot represent multi-seller carts, shared shipping, partial refunds,
discount allocations, or split settlements safely.
