# NeoGiga Reference Projects Full Index

Scan date: 2026-07-06
Reference folder used: `/Users/ashokdhamala/Desktop/project reference`
Temporary extracted scan copy: `/tmp/neogiga-reference-rescan`

The original reference folder was not modified. Nested archives were extracted only into the temporary scan copy.

## Projects Found

Total source roots found: 13

| Project | Stack | Useful modules | Quality | NeoGiga fit | Risk | Best use |
|---|---|---:|---:|---:|---|---|
| Smartend | Laravel + Blade | admin, CMS, settings, menus, media, SEO, roles | 8 | 8 | Medium | Admin dashboard structure and CMS/settings patterns |
| Digikash | Laravel + Blade/Vite | wallet, transactions, gateways, merchant, agent, referral, gift card, notifications | 8 | 9 | High | Payment, wallet, affiliate/agent architecture reference |
| UltimatePOS | Laravel + Blade | POS, stock, purchase, suppliers, cash register, accounting, reports | 9 | 9 | High | Inventory/POS/ERP reference model |
| Smart POS SaaS | Laravel + Blade/Vite | POS, multistore, product, stock, sales, payment | 8 | 8 | High | POS SaaS workflow reference |
| Salesy SaaS CRM | Laravel + JS | invoices, purchase orders, payments, referrals, coupons, email templates | 7 | 7 | Medium | ERP/reporting/payment-provider pattern reference |
| Radminly | Laravel + Blade | admin, roles, inventory UI, accounting UI | 6 | 6 | Medium | Lightweight UI/menu inspiration |
| LivaChat | Laravel + Blade | live chat, tickets, email campaigns, notifications, OTP | 7 | 6 | Medium | Notification/support/campaign reference |
| TicketGo | Laravel + Blade | support tickets, notification templates, roles | 6 | 5 | Medium | Support/ticket notification reference |
| SBURK | Laravel + Blade | subscriptions, Flutterwave payment, SMS | 5 | 4 | Medium | Payment gateway cautionary reference |
| iTest | CodeIgniter/PHP | exams, permissions, reports | 4 | 3 | High | Ignore except legacy report ideas |
| Qunzo Gift Cards addon | SQL/addon | gift cards | 4 | 4 | High | Gift-card schema comparison only |
| WhatsApp SaaS addon | docs/addon | WhatsApp API | 3 | 3 | High | Ignore until source verified |
| WhatsCRM Web Push addon | docs/addon | push notification | 3 | 3 | High | Ignore until source verified |

## Exact Source Roots

- Smartend: `/tmp/neogiga-reference-rescan/smartend-/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core`
- Digikash: `/tmp/neogiga-reference-rescan/digikash-20/core-v2.0`
- UltimatePOS: `/tmp/neogiga-reference-rescan/ultimatepos/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`
- Smart POS SaaS: `/tmp/neogiga-reference-rescan/smartpos-221/dist/pos-saas`
- Salesy SaaS CRM: `/tmp/neogiga-reference-rescan/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file`
- Radminly: `/tmp/neogiga-reference-rescan/radmily-400/codecanyon-26005211-laravel-admin-template-roles-permission-editable-datatables/radminly`
- LivaChat: `/tmp/neogiga-reference-rescan/livachat-20/codecanyon-54326601-livachat-laravel-open-source-live-chat-application/LivaChat`
- TicketGo: `/tmp/neogiga-reference-rescan/ticketgo-67/codecanyon-23051838-support-ticket-system/main-file`
- SBURK: `/tmp/neogiga-reference-rescan/sburk-35/codecanyon-25026826-sburk-school-bus-tracker-two-android-apps-backend-admin-panels-saas/SBURK/backend`
- iTest: `/tmp/neogiga-reference-rescan/itest-49`
- Qunzo Gift Cards: `/tmp/neogiga-reference-rescan/qunzogiftcards-10`

## Module Summary

Best references:
- Backend admin dashboard: Smartend.
- Payment/wallet/affiliate: Digikash.
- Multi-location inventory: UltimatePOS, with Smart POS SaaS as comparison.
- POS: UltimatePOS, with Smart POS SaaS as UI/workflow reference.
- ERP/reporting: UltimatePOS for accounting/reporting, Salesy for invoice/purchase-order workflows.
- Email/notification: Digikash for notification preferences/templates, LivaChat for campaigns/OTP, Salesy for email template service.
- Gift card/coupon/wallet: Digikash first, Qunzo and Salesy as secondary references.

## Security And License Notes

Most projects appear to be CodeCanyon/commercial packages. Treat all code as reference-only unless the owner confirms license rights. Do not copy source files directly into NeoGiga. Rebuild patterns in Laravel 11 with NeoGiga naming, migrations, policies, tests, and audit/source metadata.

