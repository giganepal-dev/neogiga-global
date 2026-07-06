# NeoGiga Reference Master Blueprint

## Best Sources

1. Backend dashboard: Smartend.
2. Payment/wallet/affiliate: Digikash.
3. Multi-location inventory: UltimatePOS.
4. POS: UltimatePOS, with Smart POS SaaS as secondary UI/workflow reference.
5. ERP/reporting: UltimatePOS and Salesy.
6. Email/notification: Digikash, LivaChat, Salesy.
7. Gift card/coupon/wallet: Digikash for wallet/gift cards, Salesy for coupons.

## Implementation Order

1. Admin dashboard shell and settings manager from Smartend patterns.
2. Inventory/POS hardening from UltimatePOS/Smart POS patterns.
3. Payment ledger and wallet abstraction from Digikash patterns.
4. Affiliate/referral foundation from Digikash/Salesy patterns.
5. ERP reporting and exports from UltimatePOS/Salesy patterns.
6. Notification templates/preferences from Digikash/LivaChat/Salesy patterns.
7. Gift card/coupon/wallet features from Digikash/Salesy/Qunzo patterns.

## Copy Structurally

Copy ideas, not code:
- Smartend route/module separation, menu/sidebar/settings/media/SEO concepts.
- UltimatePOS stock movement, cash register, purchase receiving, report concepts.
- Digikash wallet, transaction, gateway, agent/referral concepts.
- Salesy invoice, purchase order, coupon, email template concepts.

## Rewrite Fully

- Payment providers, webhooks, payouts, refunds.
- POS sale and stock deduction code.
- Admin UI components.
- Database migrations.
- Auth, permissions, policies.
- Upload/media handling.

## Ignore

- Installer scripts.
- Demo SQL.
- `.env` and credential examples.
- Vendor folders and generated frontend builds.
- Unknown addon code without source or license clarity.

## Database Improvements

- Add idempotency keys to all money and stock mutations.
- Store amount_minor plus currency precision for money.
- Keep append-only ledgers for stock, wallet, payment, commission, and audit.
- Include country/marketplace/vendor/warehouse dimensions.
- Store source metadata for imported data per NeoGiga source URL rule.

## Security Cautions

- No direct copy from commercial packages unless license rights are confirmed.
- No live payment credentials in code.
- Verify all webhooks by signature.
- Use queues for outbound notifications.
- Do not expose admin routes publicly.
- Validate uploads by MIME, extension, size, and authorization.

