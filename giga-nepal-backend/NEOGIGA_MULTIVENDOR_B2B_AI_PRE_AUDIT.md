# NeoGiga Multi-Vendor, B2B, BOM, Distributor, and Commerce AI Pre-Audit

Date: 2026-07-07

## Scope

This audit satisfies Step 1 of the advanced commerce foundation command. No migrations, seeders, data imports, or production database mutations were run while preparing it.

Live Laravel path audited: `/home/neogiga/laravel/current`

## Existing Foundation

### Core platform

- Laravel 11.54.0 application is deployed and serving `neogiga.com`, `backend.neogiga.com`, and `admin.neogiga.com`.
- Existing auth uses `users.role_id`, `roles.permissions`, `api.token`, `permission:*`, `admin.token`, and `admin.web`.
- Admin UI exists under `resources/views/admin/*` and is protected by `admin.web`.
- API routes are centralized in `routes/api.php`; web/admin pages are in `routes/web.php`.
- IoT/device modules exist and must not be touched: devices, device types/statuses/configs, firmware, GPS/RFID/sensor logs, alerts, sites, customers, support/audit logs.

### Marketplace and catalog

Existing tables include:

- `countries`, `currencies`, `marketplaces`, `marketplace_domains`, `marketplace_settings`
- `regions`, `cities`, `tax_zones`, `delivery_zones`
- `product_categories`, `product_category_translations`, `product_brands`
- `products`, `product_images`, `product_variants`, `product_specs`, `product_spec_groups`
- `product_documents`, `product_videos`, `product_compatibility`, `product_related_items`
- `product_approval_status`, `product_lms_links`, `product_seo_meta`
- `marketplace_product_prices`, `bulk_price_tiers`, `currency_exchange_rates`
- `tax_rules`, `import_duty_rules`, `shipping_fee_rules`

Existing API coverage:

- Public marketplaces: `/api/v1/marketplaces/*`
- Public categories/brands/products: `/api/v1/categories/*`, `/api/v1/brands/*`, `/api/v1/products/*`
- Admin catalog views exist for categories, products, marketplaces, media, SEO.

### Vendor foundation

Existing vendor tables include:

- `vendors`
- `vendor_profiles`
- `vendor_marketplace_approvals`
- `vendor_warehouses`
- `vendor_documents`
- `vendor_staff`
- `vendor_payout_methods`
- `vendor_audit_logs`
- `vendor_ratings`
- `vendor_inventory`
- `vendor_product_prices`

Existing public vendor routes:

- `GET /api/v1/vendors`
- `POST /api/v1/vendors/register`
- `GET /api/v1/vendors/{slug}`
- `GET /api/v1/vendors/{vendor}/marketplace-approvals`
- `POST /api/v1/vendors/{vendor}/apply-marketplace`

Existing vendor controller status:

- Public registration validates request data and creates `vendors.status = pending`.
- Marketplace application creates `vendor_marketplace_approvals.status = pending`.
- Public approval status exposes limited non-sensitive fields.
- `Api\Admin\VendorAdminController` exists but is a stub.

### Inventory, POS, orders, and payments

Existing tables include:

- `warehouses`, `inventory_stocks`, `inventory_movements`
- `reserved_stocks`, `damaged_stocks`, `incoming_stocks`, `regional_inventory_visibility`
- `carts`, `cart_items`
- `orders`, `order_items`, `order_status_history`
- `invoices`, `invoice_items`
- `payments`, `refunds`
- `shipments`, `shipment_tracking`
- `returns`, `return_items`, `warranty_claims`
- `pos_terminals`, `pos_sessions`, `pos_sales`, `pos_sale_items`, `pos_payments`, `pos_refunds`, `pos_cash_movements`, `pos_shift_closings`

Existing services include:

- `Inventory\ReservationService`
- `Inventory\StockMovementService`
- `Inventory\TransferService`
- `Inventory\PurchaseReceivingService`
- `POS\PosService`

Existing route coverage:

- Inventory read APIs and token-protected reserve/release APIs.
- Cart and checkout APIs protected by `api.token` plus role permissions.
- POS APIs exist and are token-protected for writes.
- Admin inventory APIs exist under `/api/v1/admin/inventory/*`.

### ERP, RFQ, quotation, procurement

Existing tables include:

- `suppliers`, `purchase_orders`, `purchase_order_items`
- `rfq_requests`, `rfq_items`
- `quotations`, `quotation_items`
- `expenses`, `document_number_sequences`

Existing services/controllers include:

- `Erp\RfqService`
- `Erp\QuotationService`
- `Erp\PurchaseOrderService`
- `Api\Sales\RfqController`
- `Api\Admin\QuotationAdminController`
- `Api\Admin\ProcurementAdminController`

This overlaps with the requested B2B RFQ/quotation/purchase order foundation and should be extended, not duplicated.

### LMS and BOM-adjacent foundation

Existing LMS/project tables include:

- `lms_courses`, `lms_modules`, `lms_lessons`, `lms_projects`
- `lms_project_components`, `lms_code_samples`, `lms_product_links`
- `lms_enrollments`, `lms_progress_events`, `lms_certificates`
- `lms_assignments`, `lms_assignment_submissions`, `lms_quizzes`, `lms_quiz_questions`, `lms_quiz_attempts`

Existing BOM-adjacent tables/services:

- `product_bom_items`
- `ai_bom_builds`
- `ai_bom_items`
- `BomBuilderService`
- `LmsMatcherService`

Missing dedicated BOM project-commerce tables such as `bom_projects`, `bom_project_items`, `bom_user_builds`, and `bom_cart_conversions`.

### AI foundation

Existing AI tables include:

- Legacy/simple AI commerce: `ai_sessions`, `ai_messages`, `ai_product_recommendations`, `ai_bom_builds`, `ai_bom_items`, `ai_cart_actions`, `ai_pos_invoices`, `ai_lms_recommendations`, `ai_sample_code_snippets`
- AI knowledge platform: `ai_agents`, `ai_conversations`, `ai_tool_calls`, `ai_knowledge_sources`, `ai_documents`, `ai_document_chunks`, `ai_embeddings`, `ai_model_providers`, `ai_model_routes`, `ai_prompt_versions`, `ai_guardrail_rules`, `ai_feedback`, `ai_evaluations`, `ai_generated_boms`, `ai_cart_drafts`, `ai_quote_drafts`, `ai_order_actions`, `ai_handoff_tickets`, `ai_project_templates`

Existing AI services:

- `Ai\AiToolsContract`
- `Ai\DatabaseAiTools`
- `AiCartService`
- `AiPosInvoiceService`
- `BomBuilderService`

Existing AI routes:

- `/api/v1/ai/session`
- `/api/v1/ai/message`
- `/api/v1/ai/build-bom`
- `/api/v1/ai/add-bom-to-cart`
- `/api/v1/ai/create-pos-invoice`

Current AI controller returns `501` placeholders and is protected by `api.token`.

### Marketing, analytics, promotions, affiliate

Existing foundations include:

- Marketing CRM/newsletter/email/WhatsApp/analytics/abandoned-cart tables, services, jobs, APIs, and admin pages.
- Promotion tables/services for coupons and gift cards.
- Affiliate/referral foundation with `affiliates`, `referral_codes`, `referral_attributions`, `commission_rules`, `commission_ledger`, and payout request/batch tables.

Affiliate overlaps with distributor/reseller commission concepts and should be reused where appropriate.

## Missing Required Modules

### Multi-vendor gaps

Missing or incomplete:

- Dedicated `vendor_roles` and `vendor_permissions` tables.
- `vendor_branches`.
- `vendor_products` table if vendor-specific catalog submission must be separate from global `products`.
- `vendor_orders`, `vendor_order_items`.
- `vendor_payouts`, `vendor_payout_items`.
- `vendor_commission_rules`.
- `vendor_reviews` as separate from existing `vendor_ratings`.
- `vendor_support_tickets`.
- Seller authenticated API group: `/api/seller/*`.
- Seller dashboard APIs.
- Seller policies: `SellerPanelPolicy`, `SellerOrderPolicy`, `SellerProductPolicy`, `SellerInventoryPolicy`.
- Real admin vendor approval APIs; `Api\Admin\VendorAdminController` is currently a stub.
- Vendor approval/product/payout services required by command.

Existing `vendors.status` enum currently uses `pending`, `active`, `suspended`, `rejected`, while the command wants `draft`, `pending_verification`, `verified`, `rejected`, `suspended`, `disabled`. This requires an additive compatibility plan instead of replacing values.

### Distributor gaps

No dedicated distributor network tables were found:

- `distributors`, `distributor_profiles`, `distributor_territories`, `distributor_staff`
- `distributor_downlines`, `distributor_leads`, `distributor_customers`, `distributor_orders`
- `distributor_commission_rules`, `distributor_commissions`, `distributor_payouts`, `distributor_activity_logs`

No `/api/distributor/*` or `/api/admin/distributors/*` route groups exist.

Affiliate/referral tables exist and should be considered before duplicating commission logic.

### B2B commerce gaps

Partially covered by ERP RFQ/quotation/procurement, but missing dedicated B2B account layer:

- `b2b_accounts`, `b2b_account_users`
- `b2b_price_lists`, `b2b_price_list_items`
- `b2b_quote_requests`, `b2b_quote_items`
- `b2b_quotations`, `b2b_quotation_items`
- `b2b_purchase_orders`, `b2b_purchase_order_items`
- `b2b_credit_terms`
- `b2b_approval_workflows`, `b2b_approval_steps`
- `b2b_account_activity_logs`

No `/api/b2b/*` or `/api/admin/b2b/*` route groups exist.

The existing `rfq_requests`, `quotations`, and `purchase_orders` should either be extended with B2B account foreign keys or wrapped by B2B-specific tables that snapshot and link to them.

### BOM/project-commerce gaps

Missing dedicated BOM project-commerce tables:

- `bom_projects`, `bom_project_categories`, `bom_project_items`, `bom_project_tools`
- `bom_project_lms_links`, `bom_project_code_samples`, `bom_project_alternatives`
- `bom_project_price_snapshots`, `bom_project_build_guides`
- `bom_user_builds`, `bom_user_build_items`, `bom_cart_conversions`

No `/api/bom/*` or `/api/admin/bom/*` route groups exist.

Existing `product_bom_items` and `lms_project_components` are too thin for full project-commerce but should be linked.

### Commerce AI gaps

Existing AI tables are close but route names and table names differ from the command.

Missing command-specific tables:

- `commerce_ai_sessions`, `commerce_ai_messages`, `commerce_ai_intents`
- `commerce_ai_recommendations`, `commerce_ai_recommendation_items`
- `commerce_ai_bom_requests`, `commerce_ai_bom_results`
- `commerce_ai_cart_actions`, `commerce_ai_quote_actions`, `commerce_ai_pos_actions`
- `commerce_ai_feedback`, `commerce_ai_safety_logs`

Risk: these duplicate the existing `ai_*` and AI knowledge platform tables. Preferred approach is to extend/reuse `ai_sessions`, `ai_messages`, `ai_generated_boms`, `ai_cart_drafts`, `ai_quote_drafts`, `ai_order_actions`, `ai_feedback`, and `ai_guardrail_rules`, unless strict table names are required for external contracts.

Missing services:

- `CommerceAiService`
- `CommerceAiIntentDetector`
- `CommerceAiRecommendationService`
- `CommerceAiBomService`
- `CommerceAiQuoteService`
- `CommerceAiCartActionService`
- `CommerceAiPosActionService`
- `CommerceAiSafetyService`
- `CommerceAiContextService`

No `/api/commerce-ai/*` or `/api/admin/commerce-ai/*` routes exist.

## Duplicate and Conflict Risks

1. Vendor statuses conflict with existing schema values. Do not change enum values in-place without a compatibility migration and data mapping.
2. `vendor_ratings` already combines rating and review. Adding `vendor_reviews` may duplicate review content unless it is modeled as detailed textual review records linked to ratings.
3. `vendor_payout_methods` exists, but `vendor_payouts` and `vendor_payout_items` do not. Do not store sensitive payout account details outside existing payout method conventions.
4. Existing ERP `rfq_requests`, `quotations`, and `purchase_orders` overlap with requested B2B quote/PO tables. Do not create parallel flows that can disagree on accepted price, tax, or status.
5. Existing affiliate commission tables overlap with distributor commissions. Distributor commission logic should reuse calculation patterns or isolate carefully.
6. Existing `ai_*` and AI knowledge platform tables overlap with requested `commerce_ai_*` tables. Duplicating all tables may fragment history, feedback, safety logs, and session context.
7. Existing `product_bom_items` and LMS project tables overlap with requested BOM project tables. New BOM tables should link to products/LMS rather than copy product or LMS content.
8. Public `GET /api/v1/vendors/{vendor}/marketplace-approvals` exposes status by vendor id and is intentionally non-sensitive. Seller private data must use authenticated seller routes, not this public route.
9. Current admin API protection is `admin.token`, not full multi-user admin RBAC. New admin APIs should preserve this gate and add role/permission checks where existing user auth is available.
10. Production database currently must not be modified directly. All schema changes must be new migrations and should be executed only after approval/maintenance window.

## Files to Create

### Migrations

Create additive migrations only, grouped by domain:

- `database/migrations/marketplace/YYYY_MM_DD_HHMMSS_extend_vendor_status_and_commerce_tables.php`
- `database/migrations/marketplace/YYYY_MM_DD_HHMMSS_create_seller_panel_tables.php`
- `database/migrations/distributor/YYYY_MM_DD_HHMMSS_create_distributor_foundation_tables.php`
- `database/migrations/b2b/YYYY_MM_DD_HHMMSS_create_b2b_commerce_tables.php`
- `database/migrations/bom/YYYY_MM_DD_HHMMSS_create_bom_project_commerce_tables.php`
- `database/migrations/commerce_ai/YYYY_MM_DD_HHMMSS_create_commerce_ai_compatibility_tables.php` only if table-name compatibility is required

### Models

Create domain models under namespaces:

- `App\Models\Marketplace\VendorRole`, `VendorPermission`, `VendorBranch`, `VendorProduct`, `VendorOrder`, `VendorOrderItem`, `VendorPayout`, `VendorPayoutItem`, `VendorCommissionRule`, `VendorReview`, `VendorSupportTicket`
- `App\Models\Distributor\*`
- `App\Models\B2B\*`
- `App\Models\Bom\*`
- `App\Models\CommerceAi\*` if compatibility tables are used

### Form Requests

Create validating form requests for all write APIs:

- `App\Http\Requests\Seller\*`
- `App\Http\Requests\Distributor\*`
- `App\Http\Requests\B2B\*`
- `App\Http\Requests\Bom\*`
- `App\Http\Requests\CommerceAi\*`
- `App\Http\Requests\Admin\Vendor\*`, `Admin\Distributor\*`, `Admin\B2B\*`, `Admin\Bom\*`, `Admin\CommerceAi\*`

### Controllers

Create additive API controllers:

- `Api\Seller\SellerDashboardController`, `SellerProfileController`, `SellerProductController`, `SellerInventoryController`, `SellerOrderController`, `SellerPayoutController`, `SellerSupportTicketController`
- `Api\Admin\VendorAdminController` should be implemented rather than replaced.
- `Api\Distributor\DistributorController`, `DistributorDashboardController`, `DistributorLeadController`, `DistributorCustomerController`, `DistributorOrderController`, `DistributorCommissionController`, `DistributorPayoutController`
- `Api\Admin\DistributorAdminController`
- `Api\B2B\B2BAccountController`, `B2BRfqController`, `B2BQuotationController`, `B2BPurchaseOrderController`
- `Api\Admin\B2BAdminController`
- `Api\Bom\BomProjectController`, `BomUserBuildController`
- `Api\Admin\BomAdminController`
- `Api\CommerceAi\CommerceAiController`
- `Api\Admin\CommerceAiAdminController`

### Services

Create services requested by the command, but reuse existing services where possible:

- Vendor: registration, approval, product, pricing, inventory, order, payout, commission, performance.
- Seller dashboard service.
- Distributor services.
- B2B services.
- BOM services.
- Commerce AI services.
- Visibility/resolver services: `ProductVisibilityService`, `VendorVisibilityService`, `MarketplacePricingResolver`, `MarketplaceStockResolver`, `B2BPriceResolver`, `CommerceEligibilityService`.

### Seeders and docs

- `database/seeders/VendorCommerceSeeder.php`
- `database/seeders/DistributorSeeder.php`
- `database/seeders/B2BSeeder.php`
- `database/seeders/BomProjectSeeder.php`
- `database/seeders/CommerceAiLocalRuleSeeder.php`
- Documentation files listed in Step 10.

## Files to Modify

- `routes/api.php`: add route groups for `/api/v1/seller`, `/api/v1/distributor`, `/api/v1/b2b`, `/api/v1/bom`, `/api/v1/commerce-ai`, and protected admin groups.
- `routes/web.php`: optionally add seller/distributor/admin placeholder pages only after API foundation exists.
- `app/Http/Controllers/Api/Admin/VendorAdminController.php`: implement existing stub.
- `app/Models/Marketplace/Vendor.php`: add relationships and status helpers, keeping existing fields compatible.
- `app/Models/User.php` and `app/Models/Role.php`: only if role-permission helpers need additive seller/distributor/B2B checks.
- `database/seeders/DatabaseSeeder.php`: call new seeders idempotently.
- `CHANGELOG.md`: document every implementation pass.

## Files Not to Touch

- `.env` and `.env` backups.
- Existing IoT/device migrations, models, and data paths.
- Existing marketing, analytics, POS, LMS, inventory, and payment data unless a migration adds nullable references.
- Existing production data.
- Existing completed migrations except through new additive migrations.
- Existing public frontend routing unless a specific seller/distributor/B2B frontend phase is approved.

## Exact Implementation Plan

### Phase A: Audit and safety baseline

1. Complete this audit document.
2. Create restore backup before every deployment pass.
3. Confirm production database cutover status before any migration; do not change `.env`.
4. Run `php artisan route:list`, `php artisan migrate:status`, and `php artisan neogiga:smoke` before and after code deployment.

### Phase B: Multi-vendor and seller foundation

1. Add nullable/compatible vendor status fields rather than replacing existing enum values.
2. Create missing vendor tables: roles, permissions, branches, products, vendor orders/items, payouts/items, commission rules, support tickets, optional reviews.
3. Implement vendor services and audit logging.
4. Implement `Api\Admin\VendorAdminController`.
5. Add protected seller routes using `api.token` and seller/vendor ownership middleware/policies.
6. Add seller dashboard services and read APIs first, then product/inventory/order write APIs with form requests.
7. Add idempotent vendor role/permission and demo seller seeders.

### Phase C: Distributor foundation

1. Create distributor tables with hierarchy and circular-reference guards.
2. Implement distributor services and policies.
3. Add public apply API, authenticated distributor APIs, and admin distributor APIs.
4. Integrate commission calculations without reusing affiliate tables blindly.
5. Add demo distributor seeders.

### Phase D: B2B commerce

1. Create B2B account/user/price-list/approval/activity tables.
2. Add B2B foreign keys or linkage tables to existing RFQ/quotation/purchase order flows.
3. Implement B2B account, quote, quotation, purchase-order, price-list, and approval services.
4. Add B2B APIs and admin B2B APIs.
5. Enforce account scoping in policies.

### Phase E: BOM project commerce

1. Create dedicated BOM project tables linked to existing products/categories/LMS projects/code samples.
2. Implement server-side BOM pricing/availability/cart conversion services.
3. Add public BOM read/price APIs and authenticated user-build/cart APIs.
4. Add admin BOM management APIs.
5. Seed sample BOM projects idempotently.

### Phase F: Commerce AI local rule engine

1. Prefer extending existing `ai_*` platform tables; add compatibility `commerce_ai_*` tables only if route contract requires exact names.
2. Implement local rule intent detector for 4WD robot, smart irrigation, and school electronics lab.
3. Ensure AI never invents live stock; mark unresolved products unavailable.
4. Require explicit confirmation for add-to-cart, quote, POS invoice, order, or payment actions.
5. Log AI safety decisions and actions.
6. Add `/api/v1/commerce-ai/*` routes while preserving existing `/api/v1/ai/*` compatibility endpoints.

### Phase G: Visibility rules and final docs

1. Implement central product/vendor/price/stock/B2B/eligibility services.
2. Wire product listing and BOM/AI services through visibility resolvers.
3. Create all Step 10 documentation files.
4. Run safe verification commands and create verification report.

## Verification Plan

Safe commands:

- `composer dump-autoload -o`
- `php artisan route:list`
- `php artisan migrate:status`
- `php artisan neogiga:smoke`

Conditional commands after owner approval:

- `php artisan migrate`
- `php artisan db:seed --class=...`

Do not run:

- `migrate:fresh`
- `migrate:reset`
- `db:wipe`
- destructive truncation commands

## Recommendation

Start implementation with Phase B only: multi-vendor and seller foundation. That is the natural extension point because vendor tables already exist and admin vendor controller is currently a stub. Distributor, B2B, BOM, and Commerce AI should follow as separate additive passes after vendor ownership, approval, and audit logging are in place.
