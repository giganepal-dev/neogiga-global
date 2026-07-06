# NeoGiga Inventory Reference Map

Generated: 2026-07-06. Source folder: `/Users/ashokdhamala/Desktop/project reference`. Sanitized scan workspace: `/tmp/neogiga-reference-scan`. No reference project was modified. Secrets, `.env`, private keys, vendor, node_modules, build/cache folders, and SQL dumps were excluded from extraction.

## Best Source Projects

Manual review note: UltimatePOS has the strongest stock/POS signal but the archive name includes `nulled`, so it is **reference only / do not copy**. Use ERPGo and DepoControl as safer adaptation sources, and only compare UltimatePOS concepts at the design level.

| Project | Archive | Compatibility | Risk | Signal | Quality |
| --- | --- | --- | --- | --- | --- |
| UltimatePOS Stock Management POS | ultimatepos-71nulled | Medium | Critical | 10384 | 52 |
| ERPGo SaaS ERP CRM POS | erpgosaas-95 | High | High | 3783 | 95 |
| Salesy SaaS Business Sales CRM | salesysaas-79 | High | High | 794 | 76 |
| MailPurse Email Automation Marketing SaaS | mailpurse-193 | High | High | 433 | 75 |
| TicketGo Support Ticket System | ticketgo-67 | High | High | 348 | 86 |
| Radminly Laravel Admin Template | radmily-400 | High | High | 344 | 66 |

## Useful Files

### UltimatePOS Stock Management POS

Root: `/tmp/neogiga-reference-scan/extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`

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
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_09_24_134942_add_lot_no_line_id_to_stock_adjustment_lines_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2022_06_28_133342_add_secondary_unit_columns_to_products_sell_line_purchase_lines_tables.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ar/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ce/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/es/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/fr/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/he/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/hi/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ps/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/pt/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/tr/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/vi/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/public/js/stock_transfer.js`

**Relevant migrations/tables:**
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_02_08_111027_add_expiry_period_and_expiry_period_type_columns_to_products_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_10_31_180559_add_auto_send_sms_column_to_notification_templates_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2019_10_18_155633_create_account_types_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2014_10_12_100000_create_password_resets_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2021_03_25_170715_add_export_custom_fields_info_to_transactions_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_02_20_165505_add_is_direct_sale_column_to_transactions_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2021_08_25_114932_add_payment_link_fields_to_transaction_payments_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_10_31_122619_add_pay_terms_field_transactions_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_05_21_131607_invoice_layout_fields_for_sell_return.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2022_07_13_114307_create_purchase_requisition_related_columns.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2017_07_26_122313_create_units_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_10_03_104918_add_qty_returned_column_to_transaction_sell_lines_purchase_lines_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_04_24_105246_restaurant_fields_in_transaction_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_01_04_115627_create_sessions_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_11_02_171949_change_card_type_column_to_varchar_in_transaction_payments_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2021_10_05_061658_add_source_column_to_transactions_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2020_12_28_105403_add_whatsapp_text_column_to_notification_templates_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2019_07_13_111420_add_is_created_from_api_column_to_transactions_table.php`

**Route/API files to inspect:**
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/channels.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/console.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/web.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/api.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/install_r.php`

### ERPGo SaaS ERP CRM POS

Root: `/tmp/neogiga-reference-scan/extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file`

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
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/he.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/it.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/ja.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/nl.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/pl.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/pt-BR.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/pt.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/ru.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/tr.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Resources/lang/zh.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Controllers/TransferController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Pos/src/Resources/js/Pages/Barcode/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Http/Controllers/ProductServiceItemController.php`

**Relevant migrations/tables:**
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_08_12_105136_create_warehouses_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102335_create_purchase_invoice_item_taxes_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102336_create_purchase_return_items_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102329_create_purchase_returns_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102337_create_purchase_return_item_taxes_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_13_000002_create_transfers_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102334_create_purchase_invoice_items_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102328_create_purchase_invoices_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_02_120820_create_bank_transfer_payments_table.php`

**Route/API files to inspect:**
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/react-package-stubs/routes/web-crud.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/react-package-stubs/routes/web.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Quotation/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Webhook/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Iyzipay/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Coingate/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/AuthorizeNet/src/Routes/web.php`

### Salesy SaaS Business Sales CRM

Root: `/tmp/neogiga-reference-scan/extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file`

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
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Imports/ProductImport.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/Product.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_29_000004_create_products_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/seeders/ProductSeeder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/purchase-orders/index.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/purchase-orders/show.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Events/PurchaseOrderCreated.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/PurchaseOrderController.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/PurchaseOrder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/seeders/PurchaseOrderSeeder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/seeders/SarahJohnsonDataSeeder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/components/Barcode.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/components/payment/bank-transfer-form.tsx`

**Relevant migrations/tables:**
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_31_000013_create_purchase_order_comments_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000004_create_invoice_products_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000002_create_quote_products_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000002_create_sales_order_products_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_31_000011_create_purchase_order_products_table.php`

**Route/API files to inspect:**
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/auth.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/settings.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/console.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/web.php`

### MailPurse Email Automation Marketing SaaS

Root: `/tmp/neogiga-reference-scan/nested/mailpurse_build_1.9.3_abe4875d`

- `nested/mailpurse_build_1.9.3_abe4875d/language/en.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/template-gallery/unlayer/ecommerce-back-in-stock.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/template-gallery/unlayer/ecommerce-product-review-request.json`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Models/CampaignVariant.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_13_154419_create_campaign_variants_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/language.js`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/lang/ar.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/lang/en.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/template-gallery/text/product-launch.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/template-gallery/unlayer/product-launch-announcement.json`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/HomepageTextController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/SettingController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/SitePageController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/PublicController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/package-lock.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/views/admin/plans/form.blade.php`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/views/admin/settings/index.blade.php`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/views/public/addons/outreach.blade.php`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/views/public/home-3.blade.php`
- `nested/mailpurse_build_1.9.3_abe4875d/README.md`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/AddonController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/DeliveryServerController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Api/V1/Customer/Integrations/WordPressEventController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/BillingController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/DeliveryServerController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/InstallController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Jobs/InstallUpdateJob.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Jobs/ProcessAutomationRunJob.php`

**Relevant migrations/tables:**
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_13_154419_create_campaign_variants_table.php`

**Route/API files to inspect:**
- `nested/mailpurse_build_1.9.3_abe4875d/routes/console.php`
- `nested/mailpurse_build_1.9.3_abe4875d/routes/web.php`
- `nested/mailpurse_build_1.9.3_abe4875d/routes/api.php`

### TicketGo Support Ticket System

Root: `/tmp/neogiga-reference-scan/extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file`

- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/mathematica.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/sqf.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/mel.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/CHANGELOG.md`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/emoticons/js/emojis.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/themes/mobile/theme.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/CHANGELOG.md`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/plugins/emoticons/js/emojis.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/themes/mobile/theme.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/lsl.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/maxima.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/autohotkey.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/gauss.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/gml.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/isbl.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/julia.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/pgsql.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/puppet.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/sql.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/prism.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/quill.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/README.md`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/license.txt`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/charmap/plugin.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/charmap/plugin.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/paste/plugin.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/paste/plugin.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/vendor-all.js`

**Route/API files to inspect:**
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/routes/api.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/routes/web.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/auth.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/channels.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/console.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/web.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/api.php`

### Radminly Laravel Admin Template

Root: `/tmp/neogiga-reference-scan/extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly`

- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/product/edit.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/product/create.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/product/list.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/purchase/create.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/reports/inventory.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/people/suppliers.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/purchase/list.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/config/menu.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/accounting/banking/transfer.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/category/index.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/dashboard.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/pos.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/sale/create.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/modules/inventory.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/people/customers.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/sale/list.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/inventory_sidebar.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/inventory/layout.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/pages/charts-flot.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/pages/ui/carousel.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/pages/widgets.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/reports/index.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/settings/notifications.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/accounting/dashboard.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/accounting/expense/bill/create.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/accounting/expense/bill/edit.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/accounting/expense/bill/view.blade.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/resources/views/accounting/income/invoice/create.blade.php`

**Route/API files to inspect:**
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/channels.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/console.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/web.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/api.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/modules/settings.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/modules/inventory.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/modules/reports.php`
- `extracted/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly/routes/modules/accounting.php`

## Reusable Logic

- Strongest conceptual sources: UltimatePOS for stock ledger/POS/inventory breadth, ERPGo for purchase/supplier/account integration, DepoControl for simple movement-history logic.
- Recommended NeoGiga inventory schema: `warehouses`, `warehouse_locations`, `inventory_items`, `inventory_balances`, `inventory_reservations`, `stock_movements`, `stock_adjustments`, `stock_transfers`, `purchase_orders`, `purchase_order_items`, `supplier_profiles`, `inventory_batches`, `inventory_serial_numbers`, `low_stock_alerts`. Use append-only movements as source of truth and cached balances as derived state.

## NeoGiga Adaptation Plan

1. Treat UltimatePOS as reference only because archive is marked nulled. Extract patterns, not code.
2. Implement NeoGiga stock movement service with transactional PostgreSQL writes and idempotency keys.
3. Preserve multi-vendor/multi-warehouse boundaries: every movement needs `marketplace_id`, `vendor_id` nullable for platform stock, `warehouse_id`, `product_id`, `product_variant_id`, `quantity_delta`, `reason`, `reference_type`, `reference_id`.
4. Add reservation release flow for carts/orders and supplier purchase receiving flow.
5. Add low-stock dashboard queries and export endpoints.

## Gaps / Risks

- UltimatePOS risk is critical due `nulled` archive name.
- DepoControl is Python desktop, useful for movement concepts only.
- ERPGo purchase flows must be stripped of SaaS billing assumptions before adapting.
