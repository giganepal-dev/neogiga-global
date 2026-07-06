# NeoGiga Reference Adaptation Master Plan

Generated: 2026-07-06. This is an adaptation plan only. No implementation was performed.

## Best Sources By Module

| Module | Best source | Archive | Compatibility | Risk | Decision |
| --- | --- | --- | --- | --- | --- |
| LMS | SkillGro Course LMS Laravel Script | skillgro-340 | High | High | Adapt only; refactor into NeoGiga services |
| Inventory | ERPGo SaaS ERP CRM POS + DepoControl concepts; UltimatePOS only as reference-only comparison | erpgosaas-95 / depocontrol-10 / ultimatepos-71nulled | Medium-High | High/Critical | Use ERPGo/DepoControl for adaptation; do not copy UltimatePOS nulled code |
| Email/marketing | MailPurse Email Automation Marketing SaaS | mailpurse-193 | High | High | Adapt only; refactor into NeoGiga services |
| Dashboard/analytics | ERPGo SaaS ERP CRM POS | erpgosaas-95 | High | High | Adapt only; refactor into NeoGiga services |
| ERP | ERPGo SaaS ERP CRM POS | erpgosaas-95 | High | High | Adapt only; refactor into NeoGiga services |
| Gift card/coupon | Qunzo Gift Cards for gift-card provider flow + ERPGo/SkillGro for coupon rules | qunzogiftcards-10 / erpgosaas-95 / skillgro-340 | High | High | Adapt only after license verification; rewrite schema/services |

## Top Files Worth Adapting

### LMS
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/app/Http/Requests/ChapterLessonRequest.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/lang/en.json`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/resources/views/frontend/student-dashboard/enrolled-courses/index.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Assignment/app/Http/Controllers/StudentAssignmentController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Assignment/app/Http/Controllers/InstructorAssignmentController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Assignment/app/Services/AssignmentService.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/Frontend/StudentDashboardController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Resources/API/CourseDetailsCollection.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/seeders/CourseSeeder.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/resources/views/course/partials/lesson-create-modal.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/resources/views/course/partials/lesson-edit-modal.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/API/DashboardController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/Frontend/LearningController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Requests/Frontend/ChapterLessonRequest.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/resources/views/frontend/instructor-dashboard/course/partials/lesson-create-modal.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/resources/views/frontend/instructor-dashboard/course/partials/lesson-edit-modal.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/resources/views/frontend/instructor-dashboard/lesson-qna/index.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/resources/views/frontend/pages/course-details.blade.php`

### Inventory
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

### Email/marketing
- `nested/mailpurse_build_1.9.3_abe4875d/app/Mail/CampaignMailable.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Notifications/SubscriberUnsubscribedNotification.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Jobs/ProcessAutomationRunJob.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/CampaignController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Services/CampaignService.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Api/V1/Customer/CampaignController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/AutomationController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Jobs/SendCampaignChunkJob.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Notifications/WelcomeSubscriberNotification.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Services/ListSubscriberService.php`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/template-gallery/unlayer/automation-abandoned-cart.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/template-gallery/unlayer/newsletter-weekly-digest.json`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Notifications/NewSubscriberNotification.php`
- `nested/mailpurse_build_1.9.3_abe4875d/language/en.json`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/views/customer/campaigns/create.blade.php`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/views/customer/campaigns/show.blade.php`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/views/public/docs.blade.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/HomepageTextController.php`

### Dashboard/analytics
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
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/Reports/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/Reports/LeadReports.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Pos/src/Resources/js/Pages/Reports/Sales.tsx`

### ERP
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealCallSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealEmailDiscussionSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealTaskSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoLeadEmailDiscussionSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoLeadSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealStageSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/Deals/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/Deals/Show.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/Deals/Show/General.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/Reports/DealReports.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Resources/js/Pages/SystemSetup/DealStages/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Quotation/src/Database/Seeders/MarketplaceSettingSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/PurchaseInvoiceItemTax.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/SalesInvoiceItemTax.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/SalesInvoiceReturnItemTax.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/SalesProposal.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/SalesProposalItemTax.php`

### Gift card/coupon
- `nested/plugin/GiftCards/GiftCardService.php`
- `nested/plugin/GiftCards/Http/Controllers/GiftCardController.php`
- `nested/plugin/GiftCards/Models/GiftCard.php`
- `nested/plugin/GiftCards/Http/Resources/GiftCardResource.php`
- `nested/plugin/GiftCards/Providers/Reloadly.php`
- `nested/plugin/GiftCards/routes/web.php`
- `nested/plugin/GiftCards/plugin.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Controllers/CouponController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Requests/StoreCouponRequest.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Requests/UpdateCouponRequest.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/Coupon.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_03_055623_create_coupons_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/seeders/CouponSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/seeders/DemoCouponSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/coupons/create.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/coupons/edit.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/coupons/index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/coupons/types.ts`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/lang/da.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/lang/nl.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Database/Migrations/2025_09_26_102347_create_credit_notes_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Listeners/CreateCreditNoteFromReturn.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Models/CreditNote.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Account/src/Resources/js/Pages/CreditNotes/View.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Resources/js/Pages/Reports/AccountBalance.tsx`

## Files / Areas Not To Use

- Any `.env`, `.env.*`, SQL dump, private key, certificate, token, installer credential file, or license-bypass file.
- Any code/assets from archives marked `nulled` unless replaced by a clean licensed source.
- Vendor, node_modules, build artifacts, generated cache, storage logs, and compiled assets where source equivalents exist.
- Payment-provider credentials and legacy checkout implementations that conflict with NeoGiga marketplace payment flow.

## Database Tables To Adapt

- LMS: courses, categories, lessons, modules, enrollments, progress, quizzes, assignments, certificates, instructors, product-course links.
- Inventory: warehouses, stock balances, stock movements, reservations, transfers, purchase orders, suppliers, serial/batch records, low-stock alerts.
- Marketing: provider configs, event webhooks, campaign analytics, template variables; reuse existing NeoGiga Phase 2 tables first.
- ERP: document sequences, suppliers, purchase invoices, accounts, ledger entries, taxes, approvals, branches, vendor ledgers.
- Gift/coupon: gift cards, code hashes, transactions, coupons, redemptions, restrictions, wallet ledger.

## Implementation Sequence

1. LMS schema and catalog/progress APIs.
2. Inventory ledger and stock reservation hardening.
3. Email provider/webhook integration on top of existing safe queue.
4. Admin dashboard analytics and export jobs.
5. ERP document numbering, supplier purchasing, and ledger posting.
6. Gift card/coupon/wallet ledger integration into checkout.

## Refactoring Rules

- Use NeoGiga namespaces, request validators, policies, API resources, service classes, and incremental migrations.
- Keep all APIs multi-country, marketplace-aware, vendor-aware where relevant, and PostgreSQL-safe.
- Add audit logging for admin writes and tests for financial/stock/discount calculations.
- Never import raw SQL; rewrite into additive Laravel migrations.
