# Changelog

All notable changes to the NeoGiga platform.

## [Unreleased] — 2026-07-14 — Catalog media, canonical brands and governed SEO rollout

### Added
- Added permission-gated product image upload, preview, reorder, primary selection, replacement, metadata, deactivation and API workflows on the existing `product_images` table. Deactivation preserves both rows and files, and public galleries expose active images only.
- Added canonical brand directory/detail pages at `/en/brands` and `/en/brand/{slug}`, marketplace-aware eligibility and cache invalidation, stable zero-product/RFQ states, and permanent redirects from legacy plural detail URLs.
- Added centralized marketplace-aware product, category, brand and manufacturer SEO patterns, reasoned robots decisions, generated/manual separation, locks, append-only versions, admin rollback and a dry-run-first chunked regeneration command.
- Added the supplied NeoGiga logo as versioned favicon/app/touch assets and as the compatible catalog placeholder/OG family without changing stored image paths or the established design.

### Production and verification
- Deployed immutable release `/home/neogiga/laravel/releases/20260714-140500-catalog-seo-media` after a validated PostgreSQL custom dump and code/config backup at `/home/neogiga/backups/catalog-seo-media-20260714-135300`.
- Generated governed SEO for all 73,058 products and 441 categories: 69,879 product and 179 category generated records changed; 3,179 product and 262 category manual/locked records were preserved. The final dry run reports zero pending generated changes and 70,058 append-only versions.
- Preserved core production counts exactly: 73,058 products, 441 categories, 469 brands, 85,392 product images, 76,882 inventory rows, 69,880 marketplace prices, zero customers and two orders.
- Verified 172 passing tests with 771 assertions, 11 intentional legacy skips, scoped Pint, route syntax, Blade/config/route caches, Vite build, desktop/mobile browser checks, canonical/redirect behavior, public/API canaries and zero browser console errors or broken images.

## [Unreleased] — 2026-07-14 — Production-safe ElecForest catalog import

### Added
- Added a non-destructive production compatibility migration that widens only product provenance URL fields from 255-character strings to text so complete source URLs are retained without truncation.
- Added a checksum-safe media fast path that reuses the complete validated WebP/AVIF derivative set when every expected file already exists, avoiding unnecessary production image decoding and resampling.
- Added an additive, resumable ElecForest import layer with complete provenance, ordered identity matching, collision-safe SKUs, category mapping, deterministic NeoGiga content, specifications/applications, editable product SEO, source-only offers, media processing, review controls and an existing-theme admin workflow.
- Added ten import/audit/resume/retry/validation/publication Artisan commands, queue jobs for import/media/derivatives/search, 20 ElecForest feature tests and eight required implementation/audit/results reports.
- Imported 3,177 sellable products as hidden drafts from 3,178 valid JSONL records; one collection page was correctly rejected, all source links are connected, and a clean full rerun was idempotent.
- Downloaded all 9,801 product-image candidates in production, stored 9,777 checksum-deduplicated inactive image rows with 63,054 derivative references, and finished with zero ElecForest queued/failed jobs.

### Data safety and verification
- Created a checksum-verified pre-migration database backup and verified migration rollback/reapply on a copy. Existing products, categories, brands, manufacturers, prices, inventory, routes, UI and unrelated worktree changes were preserved.
- Supplier observations remain isolated: zero regional warehouse, marketplace, vendor or country-price rows were created. All imported products remain review-required and `noindex,nofollow`; strict publication correctly published zero.
- Full verification passed with 169 tests, 11 intentional skips and 709 assertions, plus database integrity, storefront/admin route, regional canonical, Composer, cache, migration and frontend-build checks.
- Deployed release `/home/neogiga/laravel/releases/20260714-103845-elecforest` after two validated custom-format PostgreSQL backups; production has 3,177 hidden drafts, strict publication remains at zero, and identity, taxonomy and media-rights reviews are still required.
- Added `giga-nepal-backend/ELECFOREST_PRODUCTION_DEPLOYMENT.md` with the live release, backup checksums, source checksum, database/media totals, route canaries, isolation results and safety state.

## [Unreleased] — 2026-07-14 — Admin-configurable email and campaign operations

### Added
- Added persistent production workers for campaign preparation/marketing, transactional/webhook delivery, and customer spreadsheet imports; workers retain the existing disabled/test/approval sending gates and run at explicit low priorities.
- Added a pre-migration schema-capability guard so deployments with the older provider table can boot safely before the additive email provider columns are migrated.
- Added encrypted, database-backed SMTP and generic provider-API configuration for separate marketing and transactional channels in the existing Communication Settings admin page, with environment fallback until an admin configuration is saved.
- Added provider test actions, credential-preserving updates, explicit credential clearing, SMTP TLS/SSL controls, API endpoint/account settings, allowlisted test recipients, rate/daily limits, and sanitized configuration status that never returns saved secrets.
- Added editable sender identities with audited enablement and verification confirmation while retaining verified-sender, consent, suppression, campaign-approval, test-mode, and production-send gates.
- Expanded the existing email admin into a complete HTML/plain-text composer and content library, template/segment campaign creation, scheduling, explicit live authorization, preparation/run, pause/resume/cancel, and message-status workflow.
- Linked the existing mass XLSX/XLS/CSV/ODS customer importer directly from campaign operations; imported transaction contacts remain non-marketable until consent is recorded.

### Data safety
- Reused the existing `email_provider_configs.encrypted_settings` and `webhook_secret_encrypted` columns; no destructive migration, table replacement, or existing-data rewrite is required.
- Long-running queue workers must be restarted after changing provider transports so every worker loads the newest encrypted configuration.
- Verified 149 passing backend tests with 598 assertions, 26 focused communication/import/account/checkout tests with 175 assertions, encrypted-secret non-disclosure, all 900 routes, cache compilation, Composer security audit, reference-workbook no-write dry-run, and the production frontend build.

## [Unreleased] — 2026-07-13 — Customer import and communication governance

### Added
- Added a normalized, additive customer/company/contact/address/invoice-source model with complete row provenance, import history/errors, country conflicts, consent evidence, preferences, subscriptions, central suppressions, recipient snapshots, provider events, and communication logs.
- Added a configurable XLSX/XLS/CSV/ODS customer import wizard and `neogiga:import-customers` command with header aliases, worksheet preview, validation, dry-run, resume, async processing, deduplication, safe existing-record updates, and formula-safe error/export files.
- Added permission-aware customer CRM, country summaries, segments, consent/suppression/merge/communication views, and authorized customer export within the existing admin design.
- Added separate marketing and transactional provider configuration, queues, regional sender profiles, immutable template versions, variable/content checks, campaign and newsletter approvals, test allowlists, frozen audiences, rate-limited batches, scheduling, pause/resume/cancel controls, and provider analytics.
- Added opaque confirmation-based unsubscribe and preference pages, central message eligibility, HMAC-verified idempotent webhooks, encrypted raw event storage, hard/soft-bounce and complaint handling, and country-level delivery analytics.
- Added disabled-by-default Global, India, and Nepal sender/domain foundations, SPF/DKIM/DMARC admin checklist, deliverability plan, deployment/rollback runbook, and dedicated import/communication regression tests.

### Changed
- Wired registration verification, welcome, password reset/change, seller/distributor approval, checkout/order status/tracking, and OTP communication into the higher-priority transactional queue while keeping promotional jobs on independent preparation/marketing queues.
- Upgraded Laravel from 11.54 to 12.63 with a checksum-verified Composer backup, resolving three published framework advisories; no packages or application modules were removed.

### Safety and verification
- The supplied `Customer Invoice Details` workbook dry-runs with one valid Sri Lanka row and no writes; import fixtures verify exact NORATEL identity/invoice linkage, provenance, idempotency, and no automatic marketing consent.
- Marketing remains sandboxed/disabled, transactional mail remains disabled/test-safe, and all seeded senders remain disabled/unverified. No real customer email was sent.
- Full backend verification passed: 147 tests, 11 intentional skips, 572 assertions; the focused importer/communication suite passed 24 tests with 149 assertions; Composer audit is clean; 896 routes, caches, and scheduler compile; Vite production build succeeds.

## [Unreleased] — 2026-07-13 — Logo assets and complete catalog sitemap coverage

### Added
- Added versioned NeoGiga favicon/app-icon sizes, an Apple touch icon, a 4:3 branded product placeholder, and a 1200×630 social sharing image from the supplied official logo.
- Added sitemap shards for all active brands and manufacturers, with manufacturer URLs derived safely from existing published product data when the optional identity table is not installed.
- Added regression coverage proving root, subcategory, and child-category URLs are included alongside brand and manufacturer landing pages.

### Changed
- Updated frontend/admin icon references, social-image defaults, product structured-data fallbacks, and existing placeholder rendering while retaining the current visual layout and theme.
- Kept the established placeholder URL as a compatibility wrapper so existing database image paths remain untouched.
- Corrected brand/manufacturer CollectionPage structured-data URLs to use the current localized page URL.
- Made hreflang filtering compatible with marketplace edition payloads cached before this release.

### Data safety
- This release requires no migration, seeder, import, or update to existing catalog records.
- Added template-level category media fallback and taxonomy ItemList schema for all localized category pages; existing category rows and images remain untouched.
- Verified complete live sitemap coverage for 69,875 public products and 439 active categories.

## [Unreleased] — 2026-07-13 — Technical SEO canonical and sitemap upgrade

### Fixed
- Preserved full localized catalog paths in canonical and Open Graph URLs instead of collapsing product, category, and listing pages to a marketplace homepage.
- Aligned regional canonical and hreflang hosts with each marketplace's configured canonical domain and excluded hidden/non-indexable editions from hreflang output.
- Corrected `robots.txt` to advertise `https://neogiga.com/sitemap.xml` and block crawling of private admin, API, cart, checkout, and password-reset surfaces.
- Replaced the capped catalog sitemap with a cache-safe sitemap index and 10,000-URL category/product shards; added public catalog hubs and removed fabricated `lastmod` timestamps.
- Added canonical localized URLs, images, conditional offers, ratings, breadcrumbs, and item lists to catalog structured data without changing frontend design.
- Added `noindex,follow` handling for search/faceted catalog URLs while keeping clean paginated listings self-canonical and indexable.
- Added default Open Graph/Twitter images and `noindex` protection for sensitive customer-flow pages.

### Verification
- Added canonical-domain, page-path, sitemap-sharding, structured-data, filtered-result, and pagination regression assertions.
- Created pre-change file backups under `.codex-backups/2026-07-13-seo-technical-upgrade/`.

## [Unreleased] — 2026-07-13 — Catalog gap feature fill

### Added
- Created first-pass audit reports for product catalog, product content, brand/manufacturer identity,
  category taxonomy, regional inventory, global/regional SEO, and B2B commerce.
- Added implementation plan and progress tracking documents for the catalog identity and SEO
  completion roadmap.
- Added an additive backend manufacturer identity foundation with brand/manufacturer public routes,
  product detail identity links, JSON-LD updates, and a read-only catalog audit command.

## [Unreleased] — 2026-07-13 — CodeRabbit governance review fixes

### Changed
- Tightened the Neogiga reference adaptation reports after CodeRabbit review. The reports now
  require actor-specific authorization (`admin.user`, `seller.web`, or customer ownership), seller
  split-based reporting, seller/POS object-level isolation, POS transactional idempotency, server-side
  payment-method validation, RBAC safeguards before role seeding, explicit settings precedence and
  cache invalidation, upload hardening before BOM/PCB intake, and package-by-package license/security
  review.
- Corrected dependency classification language so reviewed Packagist dependencies are not described
  as source-code reuse, and marked `milon/barcode` as LGPLv3/legal-review-required.
- Backup created before edits at `.codex-backups/coderabbit-report-fix-20260713-142617.tgz`.

## [Unreleased] — 2026-07-07 — Affiliate/Referral foundation

### Added
- **Admin dashboard UI for the adaptation modules** — the payments/promotions/affiliate/ERP modules
  were previously API-only (`/api/admin/*`, `admin.token`) with no presence in the server-rendered admin
  console. Added a **Commerce** sidebar section and six session-authed (`admin.web`) pages:
  Payments & Wallet, Coupons & Gift Cards, Affiliates, Suppliers & POs, RFQ & Quotations, Expenses & Reports.
  `Admin\DashboardController` gained six read methods (read-only aggregations); new
  `Admin\CommerceOpsController` handles guarded config actions — toggle payment provider (sandbox on/off
  only; never edits credentials or `is_live`), approve/mark-paid vendor payouts, create/toggle coupons,
  approve affiliates & commissions, record expenses. Views reuse the existing admin design system; all
  mutations are server-side and CSRF-protected. Verified on `neogiga_test` (all six render; provider toggle,
  coupon insert, expense numbering round-trip). No new tables/migrations.

- **Payments abstraction layer** (additive; WRAPS existing `payments`/`refunds`, no parallel ledger): migration `2026_07_07_170000_create_payments_abstraction_tables` — `payment_providers`, `payment_transaction_events`, `wallets`, `wallet_ledger_entries`, `vendor_payouts`, `vendor_payout_items`. Models `App\Models\Payments\*`; services `App\Services\Payments\{PaymentProviderManager,WalletService,VendorPayoutService}` + gateway contract `Contracts\PaymentGateway` and safe `Gateways\PlaceholderGateway` (no live calls, no credentials). Public read-only wallet (`GET /api/v1/wallet`, `/wallet/ledger`); admin providers/events/wallet-adjust/vendor-payouts (`admin.token`). `PaymentProviderSeeder` registers eSewa/Khalti/Fonepay/Stripe/PayPal/bank/COD/wallet as DISABLED sandbox. Wallet is row-locked, non-overspendable, append-only. Verified on `neogiga_test`. Gateway adapters + checkout integration are a later reviewed step.

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
