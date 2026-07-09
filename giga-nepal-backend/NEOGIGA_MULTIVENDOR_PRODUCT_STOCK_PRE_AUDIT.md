# NeoGiga Multi-Vendor Product Stock Pre-Audit

Date: 2026-07-08

## Scope

Audit target: multi-vendor commerce foundation, seller/distributor auth and panels, product listing depth, product documents/warranty, generic suggestions, and region/marketplace inventory.

Production app: `/home/neogiga/laravel/current`

## Existing Tables Found

- Auth and roles: `users`, `roles`
- Marketplace geography: `countries`, `regions`, `cities`, `marketplaces`
- Vendor: `vendors`, `vendor_profiles`, `vendor_marketplace_approvals`, `vendor_staff`, `vendor_roles`, `vendor_documents`, `vendor_products`, `vendor_audit_logs`
- Distributor: `distributors`, `distributor_profiles`, `distributor_territories`, `distributor_staff`, `distributor_leads`, `distributor_customers`, `distributor_activity_logs`
- Product catalog: `products`, `product_categories`, `product_brands`, `product_variants`, `product_spec_groups`, `product_specs`, `product_documents`, `product_compatibility`, `product_related_items`
- Inventory: `warehouses`, `vendor_warehouses`, `inventory_stocks`, `inventory_movements`, `reserved_stocks`, `regional_inventory_visibility`

## Missing Tables

- Vendor/product: `vendor_product_statuses`
- Product document split tables: `product_datasheets`, `product_certificates`, `product_manuals`
- Product warranty: `product_warranties`
- Generic product suggestions: `product_generic_groups`, `product_generic_suggestions`
- Inventory visibility/alerts: `inventory_reservations`, `marketplace_inventory_visibility`, `low_stock_alerts`

## Important Existing Columns

### `users`

`id`, `name`, `email`, `email_verified_at`, `password`, `role_id`, `api_token_hash`, `last_login_at`

### `vendors`

Includes `user_id`, `name`, `slug`, `email`, `phone`, `website`, `description`, `country_id`, `tax_number`, `registration_number`, `status`, `type`, `is_verified`, `metadata`, `commerce_status`, `public_profile_enabled`, and `seller_onboarding_completed_at`.

### `vendor_profiles`

Has operational profile fields, but missing several requested public/onboarding fields such as legal name, PAN/VAT naming, WhatsApp, region/city, logo/banner aliases, support email/phone.

### `distributors`

Includes `user_id`, `parent_id`, `name`, `slug`, `email`, `phone`, `type`, `status`, `country_id`, approval fields, and metadata.

### `products`

Already has vendor/brand/category, slug, SKU/MPN, descriptions, type/status, prices, stock quantity, low stock threshold, dimensions, marketplace visibility, `attributes`, `metadata`, `seo_meta`, approval fields, and rejection reason.

Missing explicit first-class fields requested by the brief: `approval_status`, `visibility_status`, `global_sku`, `model_number`, GTIN/EAN/UPC, country of origin, manufacturer/importer, warranty fields, return policy, certifications, package includes, use cases, tags, search keywords, featured/generic flags, and generic group pointer.

### `inventory_stocks`

Already has product/variant/warehouse/vendor/marketplace/SKU and quantity fields, but uses `quantity_available`, `quantity_reserved`, `quantity_damaged`, `quantity_incoming`, and `reorder_point` rather than the requested alias names. Missing explicit country/region/city, `backorder_allowed`, `quote_only`, and status.

## Existing Models Found

- Vendor/product: `App\Models\Marketplace\Vendor`, `VendorProfile`, `VendorMarketplaceApproval`, `VendorProduct`, `Product`, `ProductVariant`, `ProductSpec`, `ProductDocument`, `ProductCompatibility`, `ProductRelatedItem`
- Distributor: `App\Models\Distributor\Distributor`, `DistributorProfile`, `DistributorTerritory`, `DistributorLead`, `DistributorCommission`
- Inventory: `InventoryStock`, `InventoryMovement`, `Warehouse`
- Auth: `User`, `Role`

## Existing Routes Found

- Auth v1: `POST /api/v1/auth/register`, `POST /api/v1/auth/login`, `GET /api/v1/auth/me`, `POST /api/v1/auth/logout`
- Vendor public/admin: `GET /api/v1/vendors`, `POST /api/v1/vendors/register`, `POST /api/v1/vendors/{vendor}/apply-marketplace`, admin vendor approval routes
- Seller panel: dashboard, profile, marketplace approvals, products, inventory, orders, payouts, performance, support tickets under `/api/v1/seller/*`
- Distributor panel: dashboard, profile, territories, leads, customers, orders, commissions, payouts, downlines under `/api/v1/distributor/*`
- Inventory: public reads for product/marketplace/warehouse; protected reserve/release; admin stocks, movements, low-stock, adjust, transfer, receive
- Product public: products, search, category, brand, show

## Existing Middleware and Policies

- Middleware: `AuthenticateApiToken`, `EnsureAdminToken`, `EnsurePermission`
- Seller policies: `SellerPanelPolicy`, `SellerProductPolicy`, `SellerInventoryPolicy`, `SellerOrderPolicy`
- No `app/Http/Resources` directory exists yet.

## Existing Services Found

- Vendor: registration, approval, audit, product, inventory, pricing, order, commission, payout, performance
- Distributor: registration, approval, territory, dashboard, lead, activity logger, context
- Seller: context and dashboard
- Inventory: stock movement, reservation, transfer, purchase receiving

## Already Complete

- Core users/roles token auth foundation exists.
- Vendor marketplace approval foundation exists.
- Seller protected panel route groups exist.
- Distributor protected panel route groups exist.
- Admin approval endpoints exist for vendors, distributor, seller application, and distributor application.
- Region/country/city/warehouse tables exist.
- Inventory stocks and movements exist.
- Product variants/specs and product documents base table exist.
- Vendor audit logs and distributor activity logs exist.

## Partial

- Auth is versioned under `/api/v1/auth/*`; exact `/api/auth/*`, `/api/seller/login`, and `/api/distributor/login` aliases are missing.
- Seller/distributor registration exists indirectly through vendor/distributor application flows, but dedicated login/register resources are incomplete.
- Product listing supports basic variants/specs, but requested attributes/options/templates/warranty/datasheet/generic-suggestion APIs are incomplete.
- Inventory supports stocks/movements, but public product stock by region/marketplace and alias fields need a compatibility layer.
- Product approval flow exists for vendor products, but deep product document/warranty approval auditing is incomplete.
- Seller dashboard exists, but requested document/audit logs/product status summary endpoints are incomplete.
- Distributor dashboard exists, but requested territory stock/vendors/products summaries are incomplete.

## Missing

- First-class product warranty/datasheet/manual/certificate tables.
- Generic product group and suggestion tables/APIs.
- Marketplace inventory visibility table and low-stock alert table.
- Product public endpoints for attributes/specs/variants/datasheets/warranty/generic suggestions/compatibility.
- Seller product endpoints for images/datasheets/documents/variants/attributes/specs/warranty.
- Admin product attribute/spec-template/generic group management routes.
- Dedicated seller/distributor auth resources and non-versioned aliases.
- `UserResource`, `SellerResource`, and `DistributorResource`.

## Migration Conflicts

- Do not recreate existing `vendors`, `distributors`, `products`, `inventory_stocks`, `inventory_movements`, `product_variants`, `product_specs`, or `product_documents`.
- Add missing product/inventory/vendor fields with safe `Schema::hasColumn` checks.
- Add missing tables only with `Schema::hasTable` guards.
- Existing inventory quantity names differ from requested names; new code should map aliases instead of renaming columns.

## Route Conflicts

- Existing `/api/v1/products/{slug}` catch-all means product sub-routes must be registered before slug catch-all if placed inside the same group.
- Non-versioned `/api/admin/*` already exists for some modules; add aliases carefully and keep `admin.token`.
- Existing `/api/v1/distributors/apply` writes pending distributor records directly; new distributor application APIs already exist separately and should not replace it without explicit migration.

## Files To Create

- Product stock/product extension migration.
- Product catalog/document/warranty/generic suggestion models and services.
- Product catalog public extension controller.
- Seller product extension controller/request set.
- Admin product extension controller/request set.
- Auth alias/resource classes if implementing auth slice.
- Documentation guides and verification reports required by the brief.

## Files To Modify

- `routes/api.php`
- Existing product/seller/admin/distributor controllers only where extension points are safer than new controllers.
- `CHANGELOG.md`

## Files Not To Touch

- `.env`
- Existing IoT/device migrations, models, and controllers
- Existing seeded data
- Existing marketplace/product/vendor/inventory base migrations
- Existing production database data except additive migration records and clearly marked verification records

## Recommended First Implementation Slice

Complete the product-stock visibility layer first:

1. Add product datasheet/warranty/generic suggestion and marketplace stock/low-stock tables.
2. Add public product detail endpoints for stock, specs, variants, datasheets, warranty, generic suggestions, compatibility, and related/accessories.
3. Add seller/admin upload/management endpoints with validation and audit logging.
4. Add region/marketplace stock summary endpoints using existing inventory tables.

This slice fills missing customer-facing commerce behavior without destabilizing auth or existing seller/distributor dashboards.
