# Implement admin dashboard analytics using ERPGo/Salesy/Radminly patterns

Use this in a future implementation phase. Do not run destructive commands. First read `NEOGIGA_DASHBOARD_REFERENCE_MAP.md` and `NEOGIGA_REFERENCE_LICENSE_SECURITY_REVIEW.md`.

## Source Files To Inspect First

### ERPGo SaaS ERP CRM POS
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Pos/src/Http/Controllers/DashboardController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Pos/src/Resources/js/Pages/Dashboard/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Pos/src/Resources/js/Pages/Reports/Customers.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Pos/src/Resources/js/Pages/Reports/Products.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Taskly/src/Resources/js/Pages/Report/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Resources/js/Pages/Revenues/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Resources/js/Pages/ChartOfAccounts/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Resources/js/Pages/Dashboard/ClientDashboard.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Resources/js/Pages/Dashboard/CompanyDashboard.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Resources/js/Pages/SystemSetup/RevenueCategories/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Resources/js/Pages/Reports/AccountBalance.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Services/ReportService.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Hrm/src/Resources/js/Pages/Dashboard/company-dashboard.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealEmailDiscussionSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/Reports/DealReports.tsx`

### UltimatePOS Stock Management POS
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/en/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/account_reports/payment_account_report.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/activity_log.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/ReportController.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/plugins/AdminLTE/js/pages/dashboard.js`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/dashboard_configurator/partials/widget.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/home/index.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/contact.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/customer_group.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/expense_report.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/gst_sales_report.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/payment_by_age_report.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/purchase_payment_report.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/purchase_report.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/report/register_report.blade.php`

### SmartEnd Laravel Admin Dashboard
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/ar/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/br/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/ch/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/de/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/en/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/es/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/fr/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/hi/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/pt/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/ru/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/resources/views/dashboard/analytics/list.blade.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/app/Http/Controllers/Dashboard/AnalyticsController.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/lang/th/backend.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/resources/views/dashboard/analytics/ip.blade.php`
- `extracted/smartend-1200nulled/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core/resources/views/dashboard/analytics/visitors.blade.php`

## Implementation Command

```text
Audit the current NeoGiga Laravel app first. Then implement Dashboard/analytics as an additive integration layer only. Reuse existing routes, models, services, migrations, admin UI, and data where present. Do not delete or overwrite existing code/data. Create a restore point before migrations. Rewrite reference logic into NeoGiga namespaces and PostgreSQL-safe incremental migrations. Do not copy .env, SQL dumps, credentials, vendor/node_modules, or nulled code. Add request validation, policies/admin guards, API resources, service classes, audit logs for admin writes, docs, and focused tests where the app supports tests. Update CHANGELOG.md.
```

## Required NeoGiga Work Items

- Add DashboardMetricsService and date filter DTO.
- Add sales, inventory, vendor, CRM, marketing, LMS report endpoints.
- Add CSV export jobs and admin UI filters.
- Cache expensive aggregates safely.
- Test role/admin protection and date ranges.

## Safety Checklist

- Backup before migrations/imports.
- Incremental migrations only.
- No raw SQL import.
- No secret copying.
- No real provider sending/payment behavior unless explicitly enabled.
- Update docs and CHANGELOG.md.
