# Changelog

All notable changes to the NeoGiga platform.

## [Unreleased] — 2026-07-07 — Affiliate/Referral foundation

### Wired
- **Coupon + Gift Card consumption wired into checkout** (server-side): `OrderController@checkout` re-validates the cart's coupon (from `carts.metadata`) and reduces the order total, redeems the coupon (usage logged), and redeems a gift card up to the amount due (creating a `captured` gift_card payment + a `pending` payment for the remainder). Public `POST /api/v1/cart/apply-coupon`, `DELETE /api/v1/cart/coupon`, `POST /api/v1/cart/apply-gift-card` (api.token). No-promo checkout unchanged (Phase1CheckoutTest still passes). New `Phase1PromoCheckoutTest` (coupon+gift-card → discount_total 4.00, grand 35.98, amount_due 15.98).

### Added (adaptation cycle — Affiliate only; all other command modules already existed)
- **Affiliate/referral module** (additive, verified on `neogiga_test`, not yet deployed/wired):
  - Migration `2026_07_07_120000_create_affiliate_foundation_tables` — 7 tables:
    `affiliates`, `referral_codes`, `referral_attributions`, `commission_rules`,
    `commission_ledger`, `affiliate_payout_requests`, `affiliate_payout_batches`.
  - Models `App\Models\Affiliate\*` (6); services `App\Services\Affiliate\{AffiliateService,CommissionCalculationService}`.
  - Controllers `Api\Affiliate\AffiliateController` (apply/dashboard/track) and
    `Api\Admin\AffiliateAdminController` (affiliates/commissions/payouts/rules).
  - Routes appended to `routes/api.php` (public api.token / admin admin.token).
  - Safety: server-side amounts, self-referral guard, idempotent (unique order+affiliate),
    commissions pending until order paid/delivered, no auto-payout, hashed IP/UA, immutable ledger amounts.
- Audit docs: `NEOGIGA_ADAPTATION_COMMANDS_SUMMARY`, `NEOGIGA_PRE_ADAPTATION_AUDIT`,
  `NEOGIGA_ADAPTATION_IMPLEMENTATION_REPORT`, `NEOGIGA_ADAPTATION_VERIFICATION_REPORT`,
  `NEOGIGA_NEXT_IMPLEMENTATION_COMMAND`.

- **Affiliate wired into checkout + auth** (guarded, additive): `OrderController@checkout` → `recordConversion`; `AuthController` register/login → `bindReferral`/`attributeUser`; `AffiliateSeeder` default 5% rule.
- **ERP procurement foundation** (additive): migration `2026_07_07_130000_create_erp_procurement_tables` — `document_number_sequences`, `suppliers`, `purchase_orders`, `purchase_order_items`; models `App\Models\Erp\*`; services `App\Services\Erp\{DocumentNumberService,PurchaseOrderService}` (server-side totals, race-safe PO numbering, receive with over-receive cap); `Api\Admin\ProcurementAdminController` + admin routes. Verified on `neogiga_test`.

- **ERP finance: expenses + reports** (additive): migration `2026_07_07_160000_create_erp_expenses_table` — `expenses`; model `App\Models\Erp\Expense`; `App\Services\Erp\ReportService` (read-only procurement/supplier-spend/quotation/expense aggregations); `Api\Admin\FinanceAdminController` (expense CRUD + `GET /api/admin/reports/{procurement,supplier-spend,quotations,expenses}`). `EXP-` numbering via DocumentNumberService. Completes the ERP module. Verified on `neogiga_test`.
- **ERP B2B RFQ + Quotations** (additive): migration `2026_07_07_150000_create_erp_rfq_quotation_tables` — `rfq_requests`, `rfq_items`, `quotations`, `quotation_items`; models `App\Models\Erp\{RfqRequest,RfqItem,Quotation,QuotationItem}`; services `App\Services\Erp\{RfqService,QuotationService}` (server-side quote totals, RFQ→quoted→accepted lifecycle, expiry guard); public `Api\Sales\RfqController` (submit RFQ, view/accept quotes — ownership-checked) + admin `QuotationAdminController` (review RFQs, issue/send quotes). Reuses `DocumentNumberService` (RFQ-/QUO- numbers). Verified on `neogiga_test`.
- **Coupon + Gift Card foundation** (additive): migration `2026_07_07_140000_create_coupon_giftcard_tables` — `coupons`, `coupon_redemptions`, `gift_cards`, `gift_card_transactions`; models `App\Models\Promotion\*`; services `App\Services\Promotion\{CouponService,GiftCardService}` (server-side discount, usage/min-order/expiry guards, row-locked non-overspendable gift cards, append-only ledger); public validate/check (`/api/v1/coupons/validate`, `/api/v1/gift-cards/check` — validate only, use server cart subtotal) + admin CRUD. Verified on `neogiga_test`. NOT wired into cart/checkout consumption yet.

### Notes
- Payments-abstraction remains the genuine gap (NEEDS HUMAN REVIEW — overlaps existing `payments`/`refunds`).
- Coupon/gift-card consumption (redeem at checkout) is built in the services but not yet called from OrderController (deferred, like affiliate wiring was).
- PO receiving does not yet post into inventory stock (documented hook to existing StockMovement service).

### Ops
- **Deploy rule added:** `php artisan config:cache` MUST be the last deploy step — `composer dump-autoload` clears the config cache and Laravel 11 then falls back to sqlite → site-wide 500. (Caused a brief prod outage 2026-07-07, fixed immediately.)

## [0.2.0] — 2026-07-06 — Phase 0-R "Repair & Foundation"

### Audit (Phase A)
- Added full audit set: CURRENT_CODEBASE_AUDIT, ARCHITECTURE/SECURITY/SEO/DATABASE/PERFORMANCE
  gap reports, PHASE_IMPLEMENTATION_PLAN.

### Fixed
- **Fatal namespace imports** in `MarketplaceController`, `MarketplaceResolverService`,
  `AiCartService`, `BomBuilderService` (`App\Models\X` → `App\Models\Marketplace\X`).
- **Marketplace migrations never loading** — registered `database/migrations/marketplace`
  in `AppServiceProvider` (91 migrations now run).
- `products/search` route shadowed by `products/{slug}`; static segments now precede catch-alls.
- `Cart` model: wrong `User` reference, missing `calculateTotal()`; added missing
  `CartItem`, `MarketplaceProductPrice`, `VendorProductPrice`, `ProductSeoMeta`,
  `ProductBomItem`, `ProductLmsLink` models so no relation can fatal.
- Resolver: cached (1h), null-safe, port-stripping host parse.
- Seeder: hardcoded `admin123` password replaced with env/random + one-time print.

### Added
- **API v1** under `/api/v1`: implemented catalog reads (marketplaces, categories incl. cached
  tree, brands, products + search/by-category/by-brand, vendors incl. validated registration,
  inventory availability). Unimplemented commerce endpoints (cart, checkout, orders, POS, AI,
  LMS, admin import/export) return structured **501** instead of fataling.
- **Security foundation:** `SecurityHeaders` middleware (CSP, XFO, nosniff, HSTS…),
  `EnsureAdminToken` fail-closed admin gate, rate limiters (60/min API, 10/min anonymous
  writes), `.env.example`.
- **SEO foundation:** NeoGiga landing page (SSR Blade, brand theme navy/cyan/gold, semantic
  HTML, meta/OG/Twitter/canonical/hreflang, JSON-LD Organization/WebSite/Breadcrumb/FAQ,
  country+language switcher placeholders), `config/seo.php`, robots.txt with AI-crawler
  policy, `llms.txt`, dynamic `/sitemap.xml`.
- **Category taxonomy seed:** 27 engineering root categories + ~130 subcategories with SEO
  meta, visibility flags, LMS topic hints (idempotent).
- **AI commerce foundation:** `AiToolsContract` + `DatabaseAiTools`
  (searchProducts, getProductDetails, getRegionalInventory, createProjectBOM, findLMSLessons,
  createCart, createQuote, createPaymentLink, handoffToHuman) — all facts DB-sourced; missing
  capabilities throw `AiToolUnavailableException` rather than fabricate. No paid AI API wired.
- Docs: README, IMPLEMENTATION_SUMMARY, NEXT_PHASE_BACKLOG, DEPLOYMENT_NOTES, ENV_EXAMPLE,
  VALIDATION_REPORT; `composer.lock` committed.

### Known issues (tracked in NEXT_PHASE_BACKLOG.md)
- 34 empty-shell migrations (AI/POS/LMS/import-export) pending schema reconciliation.
- Orphaned root `app/` tree pending merge.
- No user auth yet (Sanctum in Phase 1); admin gate is interim.

## [0.1.0] — earlier — Initial scaffold
- Laravel 11 skeleton, marketplace schema design (91 migrations), marketplace/vendor/product
  models and seeders, mock AI services, legacy IoT schema preserved.
