# 8 — POS Feature Gap Report (2026-07-12)

**NeoGiga POS today: MISSING entirely** — no POS controllers, routes, views, or cash-session
schema anywhere on main or branches (verified by controller inventory). The reference's
Admin/Seller `POSController` is the pattern source. Memory note: an inventory/POS parallel build
existed on prod once (`migrations/inventory_pos` dir is registered in AppServiceProvider) — that
directory currently holds inventory schema only, no POS transaction tables.

## What NeoGiga already has that the POS must reuse (do NOT rebuild)

| Existing asset | Role in POS |
|---|---|
| `CartService` (branch `claude/release-a-cart-auth`) | server-authoritative pricing/stock for POS cart |
| `InventoryStock` (product+variant+warehouse+marketplace) | stock lookup & deduction source of truth |
| `MarketplaceProductPrice` / PricingRuleResolver | price resolution incl. seller/reseller scopes |
| Checkout/order pipeline + DocumentNumberService | order + receipt/invoice numbering |
| `seller.web` + `admin.user` route guards | Seller POS uses `seller.web` plus seller/warehouse scope; admin POS uses `admin.user`. Both require a shared `pos.operate` capability and route-specific policy checks |
| Icon component system | POS UI buttons/labels (WCAG AA already handled) |

## Gap table (mission features)

| Feature | State | Implementation note |
|---|---|---|
| Product search + quick view + variation picker | MISSING (UI) | catalog search API exists; POS screen is new Blade/JS view |
| Barcode scan | MISSING | input-wedge scan → SKU/barcode lookup; barcode *generation* needs `milon/barcode` |
| Cart / discount / coupon / tax | PARTIAL | CartService + tax fields exist; coupon module is an R1 dependency |
| Customer select/create | PARTIAL | customers table exists; quick-create modal new |
| Hold / resume order | MISSING | `pos_held_orders` (serialized cart) |
| Split payment / payment methods | MISSING | `pos_payments` rows per order; offline methods first (cash/card-terminal reference), gateway later |
| Invoice / receipt / print | PARTIAL | invoice HTML exists; receipt template + print CSS new; PDF via dompdf (R1 dep) |
| Stock deduction | PARTIAL | inventory movement/audit tables exist (`InventoryMovementAuditFactory`); wire POS sale to row-locked stock deduction + audit rows inside the sale transaction |
| Cash drawer session | MISSING | `pos_cash_sessions` (open/close float, variance) |
| Returns via POS | MISSING | ties to R1 refund workflow with `channel=pos` |
| Seller/warehouse scoping | PARTIAL | schema supports it; enforce seller/vendor+warehouse scope middleware on seller POS and admin policy scope on admin POS. Seller routes must not depend on admin authentication. Add **POS isolation tests** |
| Offline-ready foundation | MISSING | out of first cut; design POS endpoints idempotent (client retry-safe) as the "foundation" the mission asks for |

## Plan (Release 2, after seller portal auth exists)

1. Schema: `pos_cash_sessions`, `pos_held_orders`, `pos_payments` (+`channel` on orders).
2. `PosService` on top of CartService (price/stock authoritative). A sale commit must be one
   database transaction: validate payment totals, lock/decrement stock, create order/payment rows,
   write inventory movement audits, and rollback the whole sale on any failure. Add a unique
   idempotency key so client retries cannot double-sell or double-charge.
3. Seller-scoped routes `/seller/pos/*` behind `seller.web` + seller/warehouse middleware, and an
   admin `/admin/pos/*` variant behind `admin.user`. Both require `pos.operate`; admin-only
   middleware must not protect seller POS routes.
4. Blade POS screen (keyboard-first, barcode-wedge friendly, icon components).
5. Tests: sale happy-path, stock deduction + audit, concurrent oversell prevention, idempotent
   retry, invalid payment-total rejection, hold/resume, cash-session close variance,
   seller/warehouse isolation, and return flow. Priority P1-of-R2; risk medium (money handling —
   transactional idempotency + audit rows mandatory).
