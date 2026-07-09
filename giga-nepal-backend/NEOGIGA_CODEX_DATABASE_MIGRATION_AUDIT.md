# NeoGiga Codex Database Migration Audit

## Migration Status

- 127 migration files found.
- `php artisan migrate:status` shows migrations through batches 1-11 ran.
- Existing IoT/device tracking and Nepal geography migrations are preserved.

## Tables Found

Key tables include:
- Core/geography/IoT: `users`, `roles`, `provinces`, `districts`, `municipalities`, `wards`, `devices`, `gps_logs`, `sensor_logs`, `audit_logs`
- Marketplace: `countries`, `currencies`, `marketplaces`, `marketplace_domains`, `vendors`, `products`, `product_categories`, `product_variants`, `warehouses`
- Inventory/POS: `inventory_stocks`, `inventory_movements`, `reserved_stocks`, `pos_terminals`, `pos_sessions`, `pos_sales`, `pos_sale_items`, `pos_payments`, `pos_refunds`, `pos_shift_closings`
- Marketing/analytics: `customer_profiles`, `newsletter_*`, `email_*`, `whatsapp_*`, `analytics_events`, `trending_products`, `top_search_terms`, `regional_sales_reports`
- LMS/AI: `lms_*`, `ai_*`
- Affiliate/payment/promotion/ERP: `affiliates`, `referral_codes`, `commission_ledger`, `affiliate_payout_*`, `suppliers`, `purchase_orders`, `rfq_requests`, `quotations`, `expenses`, `coupons`, `gift_cards`
- Admin/SEO: `admin_settings`, `admin_media_assets`, `seo_pages`, `seo_redirects`

## Live Data Counts

- `countries:10`, `currencies:10`, `marketplaces:3`, `product_categories:177`
- `products:1`, `product_brands:0`, `vendors:0`, `orders:0`, `payments:0`
- `warehouses:1`, `inventory_stocks:1`, `inventory_movements:3`, `pos_sales:1`
- `affiliates:0`, `suppliers:0`, `purchase_orders:0`, `rfq_requests:0`, `quotations:0`, `expenses:0`, `coupons:0`, `gift_cards:0`

## Schema Risks

- Active DB name is `neogiga`, not required `neogiga_prod`.
- Many foundation tables are empty; launch readiness cannot be assumed from schema alone.
- Payment and wallet/store-credit ledger is not complete. `payments` exists, affiliate ledger exists, but no full wallet ledger table was verified.
- Import/export tables exist, but execution endpoints still 501.
- Product SEO table was originally a shell and later extended; existing rows may lack full metadata.
- Monetary precision and minor-unit strategy should be standardized before real payments.
- Inventory movement ledger exists but needs concurrency/idempotency tests.

## Recommended Index/FK Review

- Add/verify indexes for public lookup fields: product slug/SKU, category slug, vendor slug, marketplace domain.
- Add/verify idempotency unique indexes for stock movements, payments, refunds, POS sales, and webhooks.
- Add composite indexes by `marketplace_id`, `country_id`, `vendor_id`, `warehouse_id` on high-volume tables.
- Review cascade deletes on vendor/product/order/payment relations to avoid accidental financial or stock data loss.

