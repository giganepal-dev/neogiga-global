# NeoGiga Gift Card Coupon Reference Map

Generated: 2026-07-06. Source folder: `/Users/ashokdhamala/Desktop/project reference`. Sanitized scan workspace: `/tmp/neogiga-reference-scan`. No reference project was modified. Secrets, `.env`, private keys, vendor, node_modules, build/cache folders, and SQL dumps were excluded from extraction.

## Best Source Projects

Manual review note: broad keyword scoring ranks ERP/POS systems highly because they contain many discount, credit-note, and ledger terms. For NeoGiga gift-card work, the Qunzo plugin below is the most direct gift-card reference; ERPGo and SkillGro are better for coupon/rule patterns.

| Project | Archive | Compatibility | Risk | Signal | Quality |
| --- | --- | --- | --- | --- | --- |
| Qunzo Gift Cards Module Addon | qunzogiftcards-10 | High | High | manual direct gift-card module | adapt only |
| ERPGo SaaS ERP CRM POS | erpgosaas-95 | High | High | 16663 | 95 |
| UltimatePOS Stock Management POS | ultimatepos-71nulled | Medium | Critical | 8327 | 52 |
| SkillGro Course LMS Laravel Script | skillgro-340 | High | High | 7591 | 95 |
| TicketGo Support Ticket System | ticketgo-67 | High | High | 5575 | 86 |
| Salesy SaaS Business Sales CRM | salesysaas-79 | High | High | 4286 | 76 |
| eLMS Online Learning Management System | elms-112 | Low | High | 3852 | 62 |

## Useful Files

### Qunzo Gift Cards Module Addon

Root: `/tmp/neogiga-reference-scan/nested/plugin/GiftCards`

- `nested/plugin/GiftCards/GiftCardService.php`
- `nested/plugin/GiftCards/Http/Controllers/GiftCardController.php`
- `nested/plugin/GiftCards/Models/GiftCard.php`
- `nested/plugin/GiftCards/Http/Resources/GiftCardResource.php`
- `nested/plugin/GiftCards/Providers/Reloadly.php`
- `nested/plugin/GiftCards/routes/web.php`
- `nested/plugin/GiftCards/plugin.json`

**Relevant archive-only schema source:** `codecanyon-61891576-qunzo-gift-cards-module-addon/DB/new.sql` was found in the original archive listing but deliberately excluded from sanitized extraction. Do not import it directly; use it only as a reference while rewriting incremental Laravel migrations.

### ERPGo SaaS ERP CRM POS

Root: `/tmp/neogiga-reference-scan/extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file`

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
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Resources/js/Pages/Reports/Print/AccountBalance.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Resources/js/Pages/TrialBalance/Index.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Resources/js/Pages/TrialBalance/Print.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Services/BalanceSheetService.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/DoubleEntry/src/Services/TrialBalanceService.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/js/pages/coupons/details.tsx`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/lang/ar.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/lang/de.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/lang/en.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/resources/lang/es.json`

**Relevant migrations/tables:**
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/react-package-stubs/migrations/migration.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/react-package-stubs/migrations/create_table.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/crudly/templates/backend/package-migration.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/crudly/templates/backend/migration.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_01_15_120001_create_helpdesk_tickets_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_03_055624_create_user_coupons_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102341_create_sales_invoice_items_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/0001_01_01_000000_create_users_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_08_12_105136_create_warehouses_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102335_create_purchase_invoice_item_taxes_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_16_112923_create_ch_messages_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_16_112929_create_ch_favorites_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_08_28_083708_create_user_active_modules_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_08_25_032702_create_media_directories_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_02_043357_create_email_template_langs_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/0001_01_01_000001_create_cache_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_09_26_102336_create_purchase_return_items_table.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/database/migrations/2025_11_10_120001_create_sales_proposal_items_table.php`

**Route/API files to inspect:**
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/react-package-stubs/routes/web-crud.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/stubs/react-package-stubs/routes/web.stub`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Quotation/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Webhook/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Iyzipay/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Coingate/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/ProductService/src/Routes/web.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/AuthorizeNet/src/Routes/web.php`

### UltimatePOS Stock Management POS

Root: `/tmp/neogiga-reference-scan/extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`

- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/pt/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/public/js/pos.js`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Http/Controllers/SellPosController.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ar/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ce/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/de/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/en/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/es/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/fr/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/he/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/hi/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/id/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/lo/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/nl/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ps/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/ro/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/sq/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/tr/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/lang/vi/lang_v1.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/sale_pos/partials/edit_discount_modal.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_02_08_111027_add_expiry_period_and_expiry_period_type_columns_to_products_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/database/migrations/2018_07_25_110004_add_show_expiry_and_show_lot_colums_to_invoice_layouts_table.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/public/js/app.js`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/account_reports/trial_balance.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/invoice_layout/create.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/invoice_layout/edit.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/resources/views/sell/create.blade.php`
- `extracted/ultimatepos-71nulled/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1/app/Discount.php`

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

### SkillGro Course LMS Laravel Script

Root: `/tmp/neogiga-reference-scan/extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files`

- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2023_11_30_095404_add_wallet_balance_to_users.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/BasicPayment/app/Http/Controllers/PaymentController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/database/migrations/2023_11_29_105234_create_coupon_histories_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/BasicPayment/app/Http/Controllers/API/MpesaCallbackController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/BasicPayment/app/Http/Controllers/API/PaymentController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/BasicPayment/plugin/2checkout-php-sdk/tests/Tco/Fixtures/Tokens.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/app/Http/Controllers/CouponController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/database/migrations/2023_11_29_095126_create_coupons_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/resources/views/index.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/lang/en.json`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/public/backend/js/fontawesome-iconpicker.min.js`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/BasicPayment/app/Services/PaymentMethodService.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/app/Models/Coupon.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/app/Models/CouponHistory.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/app/Providers/CouponServiceProvider.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/app/Providers/RouteServiceProvider.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/composer.json`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/config/config.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/database/migrations/2023_11_29_113632_add_min_price_to_coupon.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/database/seeders/CouponDatabaseSeeder.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/module.json`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/resources/views/sidebar.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/routes/api.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/routes/web.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Coupon/vite.config.js`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/app/Models/Order.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/app/Traits/GiftOrderTraits.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Rules/ValidateDiscountRule.php`

**Relevant migrations/tables:**
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2025_01_13_082341_create_carts_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_04_03_044134_create_user_skill_topics_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_05_13_101258_create_lesson_replies_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2025_11_10_080309_create_user_devices_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2026_01_06_104144_add_google_event_id_to_course_live_classes_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_04_28_034905_create_quiz_question_answers_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_05_13_041532_create_quiz_results_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_05_14_114640_create_course_reviews_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_04_23_090700_create_course_chapter_lessons_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2023_11_16_063639_add_phone_to_users.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2023_11_22_105539_create_permission_tables.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_04_18_070749_create_courses_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_04_21_094841_create_course_selected_languages_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_05_13_101033_create_lesson_questions_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_02_28_064128_add_forgot_info_to_admins.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/migrations/2024_12_09_064934_favorite_course_user.php`

**Route/API files to inspect:**
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/routes/.gitkeep`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/routes/web.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Order/routes/api.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/routes/.gitkeep`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/routes/web.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/GlobalSetting/routes/api.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/routes/.gitkeep`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/routes/web.php`

### TicketGo Support Ticket System

Root: `/tmp/neogiga-reference-scan/extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file`

- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/feather.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/emoticons/js/emojis.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/plugins/emoticons/js/emojis.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/license.txt`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/license.txt`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/@fontawesome/fontawesome-free/js/v4-shims.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/composer.json`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/help/plugin.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/assets/js/plugins/tinymce/plugins/help/plugin.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/plugins/help/plugin.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/js/tinymce/plugins/help/plugin.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/@fontawesome/fontawesome-free/js/regular.min.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/gauss.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/gml.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/mathematica.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/maxima.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/puppet.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/sqf.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/sql.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/stata.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/public/libs/highlight.js/lib/languages/xl.js`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/resources/views/admin/users/setting.blade.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/README.md`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/AddOnDetails.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Helper/helper.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/AddOnController.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/AiTemplateController.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/app/Http/Controllers/Api/CustomFieldController.php`

**Relevant migrations/tables:**
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/migration.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/migration/plain.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/migration/delete.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/migration/add.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/migration/create.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/migration/drop.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2014_10_12_100000_create_password_resets_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_01_30_093411_update_description_in_conversions_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_02_07_105216_update_category_in_tickets_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2022_08_05_033142_create_email_template_langs.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2019_04_13_122456_create_notifications_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2023_06_09_085032_create_template_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_01_03_114005_add_fields_to_notification_templates_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_02_25_041424_change_template_table_name.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2019_06_29_071130_create_tickets_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2023_07_21_041247_create_login_details_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2025_06_04_060626_add_new_column_tickets_table.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/database/migrations/2019_07_02_043628_create_conversion_table.php`

**Route/API files to inspect:**
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/routes/api.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/stubs/workdo-stubs/routes/web.stub`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/auth.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/channels.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/console.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/web.php`
- `extracted/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file/routes/api.php`

### Salesy SaaS Business Sales CRM

Root: `/tmp/neogiga-reference-scan/extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file`

- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/da.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/zh.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/en.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/nl.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/pl.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/lang/tr.json`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/CouponController.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Requests/CouponRequest.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/Coupon.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_19_051735_create_coupons_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/seeders/CouponSeeder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/config/crud/coupons.ts`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/coupons/index.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/coupons/show.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/ReferralController.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/seeders/ReferralProgramSeeder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/components/app-sidebar.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/referral/components/payout-requests.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/referral/components/referral-dashboard.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/referral/components/referred-users-section.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/referral/index.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/resources/js/pages/referral/referred-users.tsx`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Helpers/helper.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/AuthorizeNetPaymentController.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Http/Controllers/InvoiceAuthorizeNetPaymentController.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/PlanOrder.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/Referral.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/app/Models/ReferralSetting.php`

**Relevant migrations/tables:**
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000001_create_notification_templates_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000003_create_invoices_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000001_create_sales_orders_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_08_08_085111_create_lead_activities_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_29_000011_create_campaign_types_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000005_create_invoice_activities_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_31_000013_create_purchase_order_comments_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_08_11_092346_create_sales_order_activities_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_10_06_083830_create_login_histories_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_12_17_000001_add_google_calendar_event_id_to_project_tasks.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000003_create_calls_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2026_02_12_000002_create_note_shares_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_27_115820_create_email_template_langs_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_29_000020_create_task_statuses_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_01_30_000002_create_meeting_attendees_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_08_11_111510_create_account_comments_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/2025_06_19_084856_create_plan_requests_table.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/database/migrations/0001_01_01_000000_create_users_table.php`

**Route/API files to inspect:**
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/auth.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/settings.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/console.php`
- `extracted/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file/routes/web.php`

### eLMS Online Learning Management System

Root: `/tmp/neogiga-reference-scan/extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web`

- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/locale/en.json`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/coupon/models/promo_code_preview_model.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/assets/languages/template.json`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/coupon/screens/coupon_screen.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/commonComp/AccessCourseNegativeWalletBalanceModal.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/commonComp/PurchaseCourseNegativeWalletBalanceModal.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/coupon/AllCoupons.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/pagesComponent/cart/CouponDialog.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/assets/languages/en_ar.json`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/core/constants/app_labels.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/cart/widgets/coupon_card.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/coupon/cubits/apply_coupon_cubit.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/coupon/models/coupon_model.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/course/widgets/coupon_section_widget.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/coupon/AddCoupon.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/coupon/EditCoupon.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/instructor/coupon/createCoupon.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/instructor/coupon/editCoupon.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/instructor/coupon/getCoupons.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/user/applyCoupon.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/user/get-cart/getAdminCoupon.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/user/getCourseCoupons.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/user/wallet/getWalletHistory.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/cart/screens/checkout_screen.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/coupon/repository/coupon_repository.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/course/screens/course_details_screen.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/wallet/widgets/wallet_card_widget.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/pagesComponent/cart/CheckoutDialog.tsx`

**Route/API files to inspect:**
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/core/routes/routes.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/core/routes/route_params.dart`

## Reusable Logic

- Qunzo Gift Cards module is the clearest gift-card reference. SkillGro Coupon module provides coupon CRUD/rules. UltimatePOS has discounts and gift-card/POS concepts but is `nulled`.
- Recommended schema: `gift_card_programs`, `gift_cards`, `gift_card_codes`, `gift_card_transactions`, `coupons`, `coupon_redemptions`, `coupon_restrictions`, `wallet_accounts`, `wallet_ledger_entries`, `loyalty_points`, `referral_rewards`. All balance changes must be ledgered and idempotent.

## NeoGiga Adaptation Plan

1. Implement gift cards as stored value with append-only transactions, not mutable balance-only records.
2. Implement coupons with validation service: active dates, usage limit, min order, country/marketplace/vendor/product/category restrictions, per-customer redemption limits.
3. Use cryptographically strong code generation, hash stored redemption code where possible, and rate-limit validation attempts.
4. Integrate with checkout pricing pipeline after inventory reservation but before payment capture.
5. Add admin audit logs for issuance, adjustment, void, and redemption.

## Gaps / Risks

- Qunzo includes raw SQL in archive; do not import it directly. Recreate as incremental Laravel migrations.
- License must be verified before copying any module code.
- Fraud prevention and ledger idempotency must be stronger than most marketplace scripts provide.
