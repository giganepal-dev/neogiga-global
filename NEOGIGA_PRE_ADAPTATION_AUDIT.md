# NeoGiga — Pre-Adaptation Audit

**Date:** 2026-07-07 · Basis: `giga-nepal-backend` (132 tables, 77+ models, 42 services). Read-only.

## Currently implemented modules (DO NOT re-create)
- **Marketplace core:** marketplaces/domains, countries/currencies, products/variants/specs/images, categories (177), brands, vendors + approvals, pricing/tax.
- **Cart/Order/Payment (base):** carts, cart_items, orders, order_items, order_status_history, payments, refunds, invoices, shipments, returns, warranty.
- **Inventory:** inventory_stocks, inventory_movements, reserved/damaged/incoming_stocks, warehouses, vendor_inventory, regional_inventory_visibility + `Services/Inventory/{Reservation,StockMovement,Transfer,PurchaseReceiving}`.
- **POS:** pos_terminals/sessions/sales/sale_items/payments/refunds/cash_movements/shift_closings + `POS/PosService`.
- **Marketing:** email/whatsapp/newsletter campaigns, customer_profiles/segments, abandoned_carts, analytics_events, OTP, 23 `Marketing/*` services, 11 `Jobs/Marketing/*`.
- **LMS:** lms_courses/lessons/projects/enrollments/certificates + `Services/Lms/*` + `/learn` pages.
- **Admin console:** admin_settings, admin_media_assets, seo_pages/redirects, marketing_admin_audit_logs + Blade admin (dashboard/inventory/pos/lms/seo/media/settings/marketing).
- **AI:** ai_* tables + tool stubs.
- **IoT/device (preserve):** devices, firmwares, gps_logs, sensor_logs, sites, network_providers, device_configs/statuses/types.

## Genuine gaps (safe to implement — no existing tables/classes)
| Module | Missing tables | Priority |
|---|---|---|
| **Affiliate/referral** | affiliates, referral_codes, referral_attributions, commission_rules, commission_ledger, affiliate_payout_requests, affiliate_payout_batches | P3 — **implementing now** |
| ERP | suppliers, purchase_orders(+items), purchase_receipts, rfq_requests(+items), quotations(+items), document_number_sequences, expenses | P4 |
| Gift-card/Coupon | coupons, coupon_redemptions, gift_cards, gift_card_transactions | P6 |
| Payments-abstraction | payment_providers, payment_transactions, payment_transaction_events, wallets, wallet_ledger_entries, vendor_payouts(+items) | P3 — overlaps existing `payments`, review first |

## Conflicts / cautions
- **`vendor_payout_methods` exists** but `vendor_payouts` does not — payout *tracking* is a gap; don't rename the existing methods table.
- **`payments`/`refunds` exist** (checkout base) — the DigCash "payment_transactions" abstraction must wrap, not replace, these. **Needs human review before implementing** to avoid double-ledger.
- **Coupon/gift-card apply** touches the existing cart/checkout server-side pricing — implement as additive validators, never trust client discount.
- No route/class-name collisions for `Affiliate*` (namespace `App\Models\Affiliate`, `App\Services\Affiliate`, `App\Http\Controllers\Api\Affiliate`).

## Plan for this cycle — Affiliate only
**Files to CREATE (additive):**
- `database/migrations/affiliate/2026_07_07_*_create_affiliate_foundation_tables.php` (7 tables, incremental)
- `app/Models/Affiliate/{Affiliate,ReferralCode,ReferralAttribution,CommissionRule,CommissionLedgerEntry,AffiliatePayoutRequest}.php`
- `app/Services/Affiliate/{AffiliateService,CommissionCalculationService}.php`
- `app/Http/Controllers/Api/Affiliate/AffiliateController.php` (apply, dashboard, track-click)
- `app/Http/Controllers/Api/Admin/AffiliateAdminController.php` (index, commissions, payout review)
- Routes appended to `routes/api.php` (validated + `api.token`/`permission:` gated for admin)

**Files to MODIFY (minimal):** `routes/api.php` (append), `CHANGELOG.md` (append), `app/Providers/AppServiceProvider.php` only if the affiliate migration subdir needs `loadMigrationsFrom` (check existing pattern first).

**Files NOT to touch:** any IoT/device, marketplace, cart/order/payment, inventory, POS, marketing, LMS migration or model; `.env`; prod DB.

## Verdict
No blocking conflict for Affiliate. Proceeding with Affiliate foundation only, on local `neogiga_test`. Payments-abstraction flagged **NEEDS HUMAN REVIEW** (overlaps live `payments`).
