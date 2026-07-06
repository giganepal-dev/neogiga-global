# NeoGiga Email Marketing Reference Map

Generated: 2026-07-06. Source folder: `/Users/ashokdhamala/Desktop/project reference`. Sanitized scan workspace: `/tmp/neogiga-reference-scan`. No reference project was modified. Secrets, `.env`, private keys, vendor, node_modules, build/cache folders, and SQL dumps were excluded from extraction.

## Best Source Projects

| Project | Archive | Compatibility | Risk | Signal | Quality |
| --- | --- | --- | --- | --- | --- |
| MailPurse Email Automation Marketing SaaS | mailpurse-193 | High | High | 6181 | 75 |
| ERPGo SaaS ERP CRM POS | erpgosaas-95 | High | High | 2722 | 95 |
| Salesy SaaS Business Sales CRM | salesysaas-79 | High | High | 1797 | 76 |
| SkillGro Course LMS Laravel Script | skillgro-340 | High | High | 1723 | 95 |
| UltimatePOS Stock Management POS | ultimatepos-71nulled | Medium | Critical | 1197 | 52 |
| TicketGo Support Ticket System | ticketgo-67 | High | High | 1124 | 86 |

## Useful Files

### MailPurse Email Automation Marketing SaaS

Root: `/tmp/neogiga-reference-scan/nested/mailpurse_build_1.9.3_abe4875d`

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
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/ListSubscriberController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/OutreachCampaignController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Http/Controllers/Customer/TemplateController.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Jobs/CheckAutomationNegativeCampaignTriggerJob.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Jobs/StartCampaignJob.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Models/Campaign.php`
- `nested/mailpurse_build_1.9.3_abe4875d/app/Models/ListSubscriber.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_13_073702_create_campaigns_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/seeders/DemoSeeder.php`
- `nested/mailpurse_build_1.9.3_abe4875d/resources/template-gallery/unlayer/automation-onboarding-series.json`

**Relevant migrations/tables:**
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_01_11_000001_create_manual_payments_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_01_13_000006_add_affiliate_payout_fk_to_commissions_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_01_01_000009_seed_ai_daily_limits_settings.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_05_20_000001_add_resend_to_delivery_server_types.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_15_000002_create_usage_logs_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_04_15_111500_add_delay_type_to_outreach_sequences_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_13_073713_create_subscriptions_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_27_000200_add_delivery_server_id_to_auto_responders_and_steps.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_15_154202_create_warmup_emails_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2024_01_01_000001_create_users_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_04_29_000001_create_list_segment_subscriber_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_15_000001_create_plans_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_03_11_000001_create_delivery_server_allocation_tables.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_01_13_000001_create_affiliates_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_13_154419_create_campaign_variants_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2024_01_01_000002_create_user_groups_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2026_01_13_000002_add_affiliate_attribution_to_customers_table.php`
- `nested/mailpurse_build_1.9.3_abe4875d/database/migrations/2025_12_13_073704_create_auto_responders_table.php`

**Route/API files to inspect:**
- `nested/mailpurse_build_1.9.3_abe4875d/routes/console.php`
- `nested/mailpurse_build_1.9.3_abe4875d/routes/web.php`
- `nested/mailpurse_build_1.9.3_abe4875d/routes/api.php`

### ERPGo SaaS ERP CRM POS

Root: `/tmp/neogiga-reference-scan/extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file`

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
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/LandingPage/src/Database/Migrations/2025_01_20_000004_create_newsletter_subscribers_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/LandingPage/src/Database/Seeders/DemoNewsletterSubscriberSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/LandingPage/src/Http/Controllers/NewsletterSubscriberController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/LandingPage/src/Http/Requests/StoreNewsletterSubscriberRequest.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/LandingPage/src/Models/NewsletterSubscriber.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/LandingPage/src/Resources/js/Pages/NewsletterSubscribers/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/settings/components/email-settings.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Controllers/NotificationTemplateController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/config/email-providers.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/EmailTemplatesSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/SupportTicket/src/Listeners/SendPublicTicketCreatedEmail.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/SupportTicket/src/Listeners/SendTicketCreatedEmail.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/SupportTicket/src/Listeners/SendTicketReplyEmail.php`

**Relevant migrations/tables:**
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/crudly/templates/backend/package-migration.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/crudly/templates/backend/migration.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_02_043357_create_email_template_langs_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2024_02_12_034023_create_notification_template_langs_table.php`

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
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/views/emails/notification.blade.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Listeners/SendInvoiceReminderEmail.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Listeners/SendLeadStatusChangedEmail.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Listeners/SendOpportunityStageChangedEmail.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Listeners/SendQuoteStatusChangedEmail.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Listeners/SendTaskAssignedEmail.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Listeners/SendUserCreatedEmail.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/settings/components/email-settings.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/da.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/en.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/nl.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/pl.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/tr.json`

**Relevant migrations/tables:**
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000001_create_notification_templates_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_29_000011_create_campaign_types_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_27_115820_create_email_template_langs_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_27_115828_create_user_email_templates_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_29_000013_create_campaigns_table.php`

**Route/API files to inspect:**
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/auth.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/settings.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/console.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/web.php`

### SkillGro Course LMS Laravel Script

Root: `/tmp/neogiga-reference-scan/extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files`

- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/subscribe_notification.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/NewsLetter/app/Emails/NewsLetterVerifyMail.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/NewsLetter/app/Emails/SendMailToNewsLetter.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/database/seeders/EmailTemplateSeeder.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/NewsLetter/resources/views/verify_mail_template.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/app/Models/EmailTemplate.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/database/migrations/2023_11_06_115856_create_email_templates_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/approved_withdraw.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/contact_mail.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/email_template.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/gift_course.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/instructor_quick_contact.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/instructor_request_approved.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/instructor_request_pending.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/instructor_request_rejected.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/live_class_mail.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/new_refund.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/order_completed.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/password_reset.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/payment_status.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/pending_wallet_payment.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/qna_reply_mail.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/refund_approval.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/rejected_refund.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/resources/views/email/template/user_verification.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/Admin/Auth/EmailVerificationNotificationController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/ContactMessage/app/Emails/ContactMessageMail.php`

**Route/API files to inspect:**
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/routes/.gitkeep`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/routes/web.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/routes/api.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/routes/.gitkeep`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/routes/web.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/routes/api.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/routes/.gitkeep`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/routes/web.php`

### UltimatePOS Stock Management POS

Root: `/tmp/neogiga-reference-scan/extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`

- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Notifications/TestEmailNotification.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2020_12_28_105403_add_whatsapp_text_column_to_notification_templates_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/NotificationTemplateController.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/NotificationTemplate.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_09_22_110504_add_sms_and_email_settings_columns_to_business_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_10_31_180559_add_auto_send_sms_column_to_notification_templates_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/notification/show_template.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/notification_template/partials/tabs.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_09_19_123914_create_notification_templates_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Utils/NotificationUtil.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/NotificationController.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/business/partials/settings_email.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/notification_template/index.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/config/disposable-email.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_06_15_173636_add_email_column_to_contacts_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_10_03_185947_add_default_notification_templates_to_database.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2020_01_09_113252_add_cc_bcc_column_to_notification_templates_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/auth/passwords/email.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/Auth/ForgotPasswordController.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Mail/ExceptionOccured.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Notifications/CustomerNotification.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Notifications/SupplierNotification.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Console/Commands/AutoSendPaymentReminder.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Notifications/RecurringExpenseNotification.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Notifications/RecurringInvoiceNotification.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/config/mail.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ar/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ce/lang_v1.php`

**Relevant migrations/tables:**
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_10_31_180559_add_auto_send_sms_column_to_notification_templates_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2020_12_28_105403_add_whatsapp_text_column_to_notification_templates_table.php`

**Route/API files to inspect:**
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/channels.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/console.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/web.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/api.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/routes/install_r.php`

### TicketGo Support Ticket System

Root: `/tmp/neogiga-reference-scan/extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file`

- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Mail/CommonEmailTemplate.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/EmailTemplateController.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Models/EmailTemplate.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Models/EmailTemplateLang.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Models/UserEmailTemplate.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2022_08_05_033142_create_email_template_langs.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2022_08_05_033151_create_user_email_templates.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2022_08_05_033235_create_email_templates_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/views/email_templates/index.blade.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/views/email_templates/show.blade.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/views/email/common_email_template.blade.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Mail/EmailTest.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/views/notification_templates/index.blade.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/NotificationTemplatesController.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/views/auth/passwords/email.blade.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/Auth/EmailVerificationPromptController.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/Auth/VerifyEmailController.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Models/NotificationTemplateLangs.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Models/NotificationTemplates.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2023_06_08_030228_create_notification_templates_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2023_06_08_030250_create_notification_template_langs_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_01_03_113952_add_fields_to_notification_template_langs_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_01_03_114005_add_fields_to_notification_templates_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/lang/ar.json`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/lang/da.json`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/lang/de.json`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/lang/en.json`

**Relevant migrations/tables:**
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2022_08_05_033142_create_email_template_langs.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2019_04_13_122456_create_notifications_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2023_06_09_085032_create_template_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_01_03_114005_add_fields_to_notification_templates_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_02_25_041424_change_template_table_name.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_01_03_113952_add_fields_to_notification_template_langs_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2023_06_08_030250_create_notification_template_langs_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2022_08_05_033151_create_user_email_templates.php`

**Route/API files to inspect:**
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/routes/api.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/routes/web.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/auth.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/channels.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/console.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/web.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/api.php`

## Reusable Logic

- Strongest source: MailPurse for subscriber/campaign/template/provider concepts. SkillGro Newsletter module can inform lightweight newsletter CRUD. TicketGo/Salesy/ERPGo include notification/event examples.
- NeoGiga already has Phase 2 tables for email templates, campaigns, subscribers, queue logs, events, OTP, abandoned carts, WhatsApp opt-ins, suppression lists, and consents. Reference code should fill provider integrations, bounce/webhook processing, and admin UX only.

## NeoGiga Adaptation Plan

1. Map MailPurse concepts into existing `App\Services\Marketing` instead of adding a parallel marketing app.
2. Add provider adapters behind `EmailProviderManager`, with test-mode defaults and per-channel suppression checks.
3. Keep unsubscribe/consent as the canonical gate; no campaign send path may bypass it.
4. Add webhook endpoints for provider events only after signed payload validation.
5. Add admin analytics cards for delivered/open/click/bounce/unsubscribe based on existing event tables.

## Gaps / Risks

- MailPurse is commercial; adapt architecture only unless license permits code reuse.
- Do not copy SMTP credentials, `.env`, or provider keys.
- Current NeoGiga safe queue is intentionally not real-send; provider work should be a separate phase.

