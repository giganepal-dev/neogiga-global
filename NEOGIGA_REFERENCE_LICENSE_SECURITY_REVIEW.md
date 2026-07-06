# NeoGiga Reference License Security Review

Generated: 2026-07-06.

## Summary

- Original archives were not modified. Sanitized extraction excluded `.env`, private keys, SQL dumps, vendor, node_modules, build/cache folders.
- Archives with `nulled` in filename are marked **do not use** for code/assets.
- CodeCanyon/Codester commercial packages are **safe to adapt only after license verification**, not automatically safe to copy.

| Project | Archive | License files | Contains .env in archive | SQL files | Risk | Reuse classification |
| --- | --- | --- | --- | --- | --- | --- |
| DepoControl Inventory Management System | depocontrol-10.rar | 0 | no | 0 | Medium | reference only until license found |
| eLMS Online Learning Management System | elms-112.rar | 0 | no | 0 | High | reference only until license found |
| ERPGo SaaS ERP CRM POS | erpgosaas-95.rar | 1 | yes | 2 | High | safe to adapt only |
| MailPurse Email Automation Marketing SaaS | mailpurse-193.rar | 0 | no | 0 | High | reference only until license found |
| QR Code Login for WhatsCRM Addon | qrlogin-22.rar | 0 | no | 0 | High | reference only until license found |
| Qunzo Gift Cards Module Addon | qunzogiftcards-10.rar | 0 | no | 1 | High | reference only until license found |
| Radminly Laravel Admin Template | radmily-400.rar | 0 | yes | 1 | High | reference only until license found |
| Salesy SaaS Business Sales CRM | salesysaas-79.rar | 0 | no | 1 | High | reference only until license found |
| SkillGro Course LMS Laravel Script | skillgro-340.rar | 5 | yes | 4 | High | safe to adapt only |
| SmartEnd Laravel Admin Dashboard | smartend-1200nulled.rar | 0 | yes | 1 | Critical | do not use / reference only |
| TicketGo Support Ticket System | ticketgo-67.rar | 3 | yes | 2 | High | safe to adapt only |
| UltimatePOS Stock Management POS | ultimatepos-71nulled.rar | 0 | yes | 1 | Critical | do not use / reference only |

## License Files Found

### DepoControl Inventory Management System
- None found in sanitized scan.

### eLMS Online Learning Management System
- None found in sanitized scan.

### ERPGo SaaS ERP CRM POS
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/LICENSE`

### MailPurse Email Automation Marketing SaaS
- None found in sanitized scan.

### QR Code Login for WhatsCRM Addon
- None found in sanitized scan.

### Qunzo Gift Cards Module Addon
- None found in sanitized scan.

### Radminly Laravel Admin Template
- None found in sanitized scan.

### Salesy SaaS Business Sales CRM
- None found in sanitized scan.

### SkillGro Course LMS Laravel Script
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/public/frontend/js/tinymce/js/tinymce/license.txt`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/public/backend/tinymce/js/tinymce/license.txt`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/public/backend/fontawesome/LICENSE.txt`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/public/backend/clockpicker/LICENSE`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/BasicPayment/plugin/2checkout-php-sdk/LICENSE`

### SmartEnd Laravel Admin Dashboard
- None found in sanitized scan.

### TicketGo Support Ticket System
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/license.txt`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/@fontawesome/fontawesome-free/LICENSE.txt`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/license.txt`

### UltimatePOS Stock Management POS
- None found in sanitized scan.

## Secret / Credential Findings

- `elms-112`: `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/public/firebase-messaging-sw.js`
- `elms-112`: `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/Firebase.ts`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Controllers/InstallerController.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Iyzipay/src/Iyzipay/IyziAuthV2Generator.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Iyzipay/src/Iyzipay/Options.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ZoomMeeting/src/Services/ZoomService.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/zh.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/tr.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/nl.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/ja.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/de.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/ru.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/pl.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/pt.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/en.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/it.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/fr.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/pt-BR.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/he.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/da.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/es.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Resources/lang/ar.json`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Http/Requests/UpdateOzowSettingsRequest.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Ozow/src/Services/OzowService.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Flutterwave/src/Http/Controllers/FlutterwaveController.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Mollie/src/Services/MolliePaymentService.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Xendit/src/Services/XenditService.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Razorpay/src/Http/Controllers/RazorpayController.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/CinetPay/src/Services/CinetPayService.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/AIAssistant/src/Services/AIService.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Benefit/src/Services/BenefitService.php`
- `erpgosaas-95`: `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Stripe/src/Http/Controllers/StripeController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/index.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Mail/CustomMailManager.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Mail/DkimSigningPlugin.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Mail/Transport/SendGridTransport.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Mail/Transport/ResendTransport.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Providers/BillingServiceProvider.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/InstallController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Webhook/MailgunWebhookController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/EmailValidationToolController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/DeliveryServerController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/SuperScrapeSettingsController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/PaymentMethodController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/AiToolController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/PublicTemplateController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Admin/IntegrationController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Api/V1/Customer/DeliveryServerController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/EmailValidationToolController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/DeliveryServerController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/TemplateController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/ScraperController.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Services/EmailitApiService.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Services/DkimSigningService.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Services/DeliveryServerService.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Services/AI/AiTemplateService.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Services/AI/AiTextToolService.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/app/Services/Billing/UddoktaPayPaymentService.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/wordpress-plugin/mailpurse-integration/src/MailPurseClient.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/resources/views/admin/delivery-servers/edit.blade.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/resources/views/admin/delivery-servers/create.blade.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/resources/views/public/docs.blade.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/resources/views/customer/delivery-servers/index.blade.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/resources/views/customer/delivery-servers/edit.blade.php`
- `mailpurse-193`: `nested/mailpurse_build_1.9.3_abe4875d/resources/views/customer/delivery-servers/create.blade.php`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/OzowPaymentController.php`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/InvoiceOzowPaymentController.php`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/InvoiceMolliePaymentController.php`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/ChatGptController.php`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Helpers/helper.php`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/zh.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/tr.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/nl.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/ja.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/de.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/pl.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/pt.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/en.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/it.json`
- `salesysaas-79`: `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/fr.json`

## Dependency / Framework Risks

- Many packages are commercial marketplace scripts with unknown license status; verify purchase/license before reuse.
- `smartend-1200nulled.rar` and `ultimatepos-71nulled.rar` are marked do-not-copy.
- Older Laravel/package versions should be assumed until composer manifests are reviewed module-by-module.
- Any provider integration, payment code, installer, updater, or license-check bypass code should be excluded from NeoGiga.
# 2026-07-06 Rescan Addendum

Reference folder used: `/Users/ashokdhamala/Desktop/project reference`

Most newly scanned packages are CodeCanyon/commercial archives. Treat all application source as reference-only unless the owner confirms license rights for code reuse.

## Classification

- Smartend: license unclear/commercial, reference only. Useful for admin structure; do not copy code/assets.
- Digikash: license unclear/commercial, reference only. High financial/security risk; rewrite all payment and wallet logic.
- UltimatePOS: license unclear/commercial, reference only. Strong POS/inventory architecture; do not copy source.
- Smart POS SaaS: license unclear/commercial, reference only. Use workflow comparison only.
- Salesy SaaS CRM: license unclear/commercial, reference only. Use invoice/purchase/payment ideas only.
- Radminly: license unclear/commercial, reference only. Use UI inspiration only.
- LivaChat/TicketGo: license unclear/commercial, reference only. Use notification/support concepts only.
- Qunzo/WhatsApp addons: source/license incomplete or narrow addon packages; unsafe to copy.

## Highest Risks

- Payment/payout/refund code in Digikash and gateway libraries.
- POS/accounting code in UltimatePOS due commercial licensing and tightly coupled legacy schema.
- Addon packages with only SQL/docs and unclear integration assumptions.
- Any `.env`, demo credential, installer, or downloaded package metadata.

## Safe First Adaptations

- Rebuild Smartend-style admin menu/settings patterns.
- Extend NeoGiga inventory/POS ledgers based on UltimatePOS concepts.
- Add Digikash-style payment provider abstraction with test/dummy provider only.
- Add notification template/preferences tables without sending real campaigns.
