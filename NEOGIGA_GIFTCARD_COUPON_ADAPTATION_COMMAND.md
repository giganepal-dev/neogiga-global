# Implement gift cards/coupons/wallet ledger from Qunzo/SkillGro patterns

Use this in a future implementation phase. Do not run destructive commands. First read `NEOGIGA_GIFTCARD_COUPON_REFERENCE_MAP.md` and `NEOGIGA_REFERENCE_LICENSE_SECURITY_REVIEW.md`.

## Source Files To Inspect First

### Qunzo Gift Cards Module Addon
- `nested/plugin/GiftCards/GiftCardService.php`
- `nested/plugin/GiftCards/Http/Controllers/GiftCardController.php`
- `nested/plugin/GiftCards/Models/GiftCard.php`
- `nested/plugin/GiftCards/Http/Resources/GiftCardResource.php`
- `nested/plugin/GiftCards/Providers/Reloadly.php`
- `nested/plugin/GiftCards/routes/web.php`
- `nested/plugin/GiftCards/plugin.json`

### ERPGo SaaS ERP CRM POS
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

### UltimatePOS Stock Management POS
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

### SkillGro Course LMS Laravel Script
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

## Implementation Command

```text
Audit the current NeoGiga Laravel app first. Then implement Gift card/coupon as an additive integration layer only. Reuse existing routes, models, services, migrations, admin UI, and data where present. Do not delete or overwrite existing code/data. Create a restore point before migrations. Rewrite reference logic into NeoGiga namespaces and PostgreSQL-safe incremental migrations. Do not copy .env, SQL dumps, credentials, vendor/node_modules, or nulled code. Add request validation, policies/admin guards, API resources, service classes, audit logs for admin writes, docs, and focused tests where the app supports tests. Update CHANGELOG.md.
```

## Required NeoGiga Work Items

- Add gift card/coupon/wallet ledger migrations.
- Add secure code generation and code hash storage.
- Add CouponValidationService and GiftCardLedgerService.
- Integrate pricing pipeline with restrictions and redemption ledger.
- Test usage limits, expiry, idempotency, and fraud throttles.

## Safety Checklist

- Backup before migrations/imports.
- Incremental migrations only.
- No raw SQL import.
- No secret copying.
- No real provider sending/payment behavior unless explicitly enabled.
- Update docs and CHANGELOG.md.
