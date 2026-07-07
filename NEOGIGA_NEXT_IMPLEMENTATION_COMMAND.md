# NeoGiga — Next Implementation Command

**State:** Affiliate/Referral foundation implemented + verified on `neogiga_test` (not deployed, not wired to checkout). Payments/ERP/Coupon are the remaining genuine gaps.

## Safest next step (do this first) — wire + activate Affiliate
Lowest-risk way to make the built module functional:
1. **Attribution capture (frontend, additive):** on `neogiga.com` category/product/landing views, if `?ref=CODE` present, call `POST /api/v1/affiliate/track` and store the returned `visitor_token` in a first-party cookie.
2. **Bind on auth (1 line):** in the existing register/login success path, if a `visitor_token` cookie exists, call `AffiliateService::attributeUser($token, $user->id)`. Additive, guarded.
3. **Record on order (1 call):** in the existing order-confirmation path (OrderController@checkout success), call `app(AffiliateService::class)->recordConversion($order)`. Creates a **pending** commission only. **Review OrderController diff before editing** (prod may be ahead — diff first).
4. **Approve on paid:** when an order transitions to paid/delivered, admin (or a job) approves via `POST /api/admin/affiliate-commissions/{entry}/approve` (already guards on order-paid).
5. Seed one default `commission_rules` row (global %); verify end-to-end on `neogiga_test`; then deploy (files + `migrate --path` on prod, **never** `migrate:fresh`).

## Then, in priority order
- **ERP foundation (P4, fully additive):** `suppliers`, `purchase_orders(+items)`, `purchase_receipts`, `rfq_requests(+items)`, `quotations(+items)`, `document_number_sequences`, `expenses` + `SupplierService`/`PurchaseOrderService`/`DocumentNumberService` + admin routes. No cart/checkout impact — safe like affiliate.
- **Gift-card / Coupon (P6):** `coupons`, `coupon_redemptions`, `gift_cards`, `gift_card_transactions` + `CouponService`/`GiftCardService`. **Touches cart/checkout** — implement as server-side validators, never trust client discount; diff CartController/OrderController first.
- **Payments abstraction (P3) — NEEDS HUMAN REVIEW:** `payment_providers`, `payment_transactions(+events)`, `wallets`, `wallet_ledger_entries`, `vendor_payouts(+items)`. Must **wrap** the existing `payments`/`refunds` tables, not create a parallel ledger. Decide the relationship before coding.

## Guardrails (unchanged)
New migrations only · no `migrate:fresh/reset/wipe` · no `.env` change · **diff shared files (routes, bootstrap/app.php, OrderController, DashboardController) against prod before deploying** — prod has an active parallel build · validate all writes · protect admin routes · server-side amounts · append-only ledgers.

## Deploy note (when ready)
`scp` changed files → `chown neogiga` → `php artisan migrate --path=<file> --force` (prod, additive) → `route:clear && route:cache && view:cache` → verify. The affiliate migration is safe to run on prod (additive, no data change).
