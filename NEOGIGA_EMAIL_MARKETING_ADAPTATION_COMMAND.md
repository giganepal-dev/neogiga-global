# Extend NeoGiga marketing provider integrations from MailPurse/SkillGro patterns

Use this in a future implementation phase. Do not run destructive commands. First read `NEOGIGA_EMAIL_MARKETING_REFERENCE_MAP.md` and `NEOGIGA_REFERENCE_LICENSE_SECURITY_REVIEW.md`.

## Source Files To Inspect First

### MailPurse Email Automation Marketing SaaS
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

### ERPGo SaaS ERP CRM POS
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Mail/CommonEmailTemplate.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/EmailTemplate.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Controllers/EmailTemplateController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Models/EmailTemplateLang.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_02_043140_create_email_templates_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_02_043357_create_email_template_langs_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/seeders/EmailTemplatesSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealEmailDiscussionSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoLeadEmailDiscussionSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Recruitment/src/Database/Seeders/EmailTemplatesSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/SupportTicket/src/Database/Seeders/EmailTemplateTableSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/EmailTemplates/Edit.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/EmailTemplates/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/settings/components/email-notification-settings.tsx`

### Salesy SaaS Business Sales CRM
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Mail/EmailTemplate.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Services/EmailTemplateService.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/EmailTemplateController.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/EmailTemplate.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/EmailTemplateLang.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/UserEmailTemplate.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_27_115807_create_email_templates_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_27_115820_create_email_template_langs_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_27_115828_create_user_email_templates_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/seeders/EmailTemplateSeeder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/email-templates/index.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/email-templates/show.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/settings/components/email-notification-settings.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/zh.json`

## Implementation Command

```text
Audit the current NeoGiga Laravel app first. Then implement Email/marketing as an additive integration layer only. Reuse existing routes, models, services, migrations, admin UI, and data where present. Do not delete or overwrite existing code/data. Create a restore point before migrations. Rewrite reference logic into NeoGiga namespaces and PostgreSQL-safe incremental migrations. Do not copy .env, SQL dumps, credentials, vendor/node_modules, or nulled code. Add request validation, policies/admin guards, API resources, service classes, audit logs for admin writes, docs, and focused tests where the app supports tests. Update CHANGELOG.md.
```

## Required NeoGiga Work Items

- Keep existing Phase 2 marketing tables as canonical.
- Add provider adapters behind EmailProviderManager in disabled/test mode by default.
- Add signed webhook event ingestion.
- Add campaign analytics dashboard.
- Test suppression/unsubscribe/consent gates.

## Safety Checklist

- Backup before migrations/imports.
- Incremental migrations only.
- No raw SQL import.
- No secret copying.
- No real provider sending/payment behavior unless explicitly enabled.
- Update docs and CHANGELOG.md.
