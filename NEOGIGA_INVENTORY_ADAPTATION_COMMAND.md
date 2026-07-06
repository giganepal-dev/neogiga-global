# Implement inventory ledger from ERPGo/DepoControl and reference-only UltimatePOS patterns

Use this in a future implementation phase. Do not run destructive commands. First read `NEOGIGA_INVENTORY_REFERENCE_MAP.md` and `NEOGIGA_REFERENCE_LICENSE_SECURITY_REVIEW.md`.

## Source Files To Inspect First

### Safe source priority
- Prefer ERPGo purchase/warehouse/supplier files and DepoControl movement-history files for adaptation.
- Treat UltimatePOS files as design reference only because `ultimatepos-71nulled.rar` is marked nulled. Do not copy code or assets from it.

### UltimatePOS Stock Management POS
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Utils/ProductUtil.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/en/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/de/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/id/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/lo/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/nl/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ro/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/sq/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/purchase_return/partials/product_table_row.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/product_purchase_report.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/stock_adjustment/partials/product_table_row.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/stock_transfer/partials/product_table_row.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/ProductController.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/PurchaseController.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/StockTransferController.php`

### ERPGo SaaS ERP CRM POS
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Database/Migrations/2025_09_13_000000_create_warehouse_stocks_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Models/WarehouseStock.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/js/Pages/Stock/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Database/Seeders/DemoProductServiceItemSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Listeners/ApprovePurchaseReturnListener.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Listeners/PostPurchaseInvoiceListener.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/js/Pages/Items/Show.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/js/Pages/Items/Create.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/js/Pages/Items/Edit.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/ar.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/da.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/de.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/en.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/es.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/fr.json`

### Salesy SaaS Business Sales CRM
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/zh.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_31_000011_create_purchase_order_products_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/products/index.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/products/show.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/nl.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/products/create.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/products/edit.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/purchase-orders/create.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/purchase-orders/edit.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/da.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/en.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/pl.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/tr.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Exports/ProductExport.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/ProductController.php`

## Implementation Command

```text
Audit the current NeoGiga Laravel app first. Then implement Inventory as an additive integration layer only. Reuse existing routes, models, services, migrations, admin UI, and data where present. Do not delete or overwrite existing code/data. Create a restore point before migrations. Rewrite reference logic into NeoGiga namespaces and PostgreSQL-safe incremental migrations. Do not copy .env, SQL dumps, credentials, vendor/node_modules, or nulled code. Add request validation, policies/admin guards, API resources, service classes, audit logs for admin writes, docs, and focused tests where the app supports tests. Update CHANGELOG.md.
```

## Required NeoGiga Work Items

- Add append-only stock_movements and derived inventory_balances migrations.
- Add StockMovementService, ReservationService, PurchaseReceivingService, TransferService.
- Wire order/cart reservation release and supplier receiving.
- Add low-stock and warehouse reports.
- Test transactionality, idempotency, and negative stock prevention.

## Safety Checklist

- Backup before migrations/imports.
- Incremental migrations only.
- No raw SQL import.
- No secret copying.
- No real provider sending/payment behavior unless explicitly enabled.
- Update docs and CHANGELOG.md.
