# NeoGiga ERP Dashboard Reference Map

Best ERP source: UltimatePOS.
Best dashboard/admin source: Smartend.
Best CRM invoice/purchase-order source: Salesy SaaS CRM.

## UltimatePOS

Root: `/tmp/neogiga-reference-rescan/ultimatepos/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`

Useful files:
- `app/Account.php`
- `app/AccountTransaction.php`
- `app/AccountType.php`
- `app/ExpenseCategory.php`
- `app/Transaction.php`
- `app/Utils/AccountTransactionUtil.php`
- `app/Utils/BusinessUtil.php`
- `app/Utils/TaxUtil.php`
- `app/Charts/CommonChart.php`
- `app/Http/Controllers/AccountController.php`
- `app/Http/Controllers/ExpenseController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/DashboardConfiguratorController.php`
- `database/migrations/2018_09_04_155900_create_accounts_table.php`
- `database/migrations/2019_10_18_155633_create_account_types_table.php`

## Salesy

Root: `/tmp/neogiga-reference-rescan/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file`

Useful files:
- `database/migrations/2025_01_30_000003_create_invoices_table.php`
- `database/migrations/2025_01_30_000004_create_invoice_products_table.php`
- `database/migrations/2025_01_31_000010_create_purchase_orders_table.php`
- `database/migrations/2025_01_29_000001_create_taxes_table.php`
- `app/Models/Invoice.php`
- `app/Models/PurchaseOrder.php`
- `app/Services/InvoicePaymentService.php`
- `app/Services/WebhookService.php`

## Smartend

Use Smartend for admin shell, menu/sidebar, settings, media, CMS, SEO, and sitemap.

## Adaptation Plan

- Keep NeoGiga marketplace/order/inventory as source of truth.
- Add ERP reporting tables only where needed: ledger snapshots, expense categories, tax profiles, invoice documents, payout reports.
- Build dashboard widgets from existing orders, products, vendors, inventory, POS, marketing, LMS, and payment ledger.
- Export reports to CSV/XLSX/PDF through queued jobs.

