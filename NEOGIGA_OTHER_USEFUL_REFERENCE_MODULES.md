# NeoGiga Other Useful Reference Modules

## Gift Cards

Best source: Digikash.

Files:
- `database/migrations/2026_05_19_021706_create_gift_card_templates_table.php`
- `database/migrations/2026_05_19_021709_create_gift_cards_table.php`
- `app/Models/GiftCardTemplate.php`
- `app/Models/GiftCard.php`
- `app/Mail/GiftCardDelivered.php`

Secondary: Qunzo `codecanyon-61891576-qunzo-gift-cards-module-addon/DB/new.sql`.

Recommendation: adapt schema concepts only; rebuild with NeoGiga wallet/payment ledger.

## Coupons

Best source: Salesy.

Files:
- `database/migrations/2025_06_19_051735_create_coupons_table.php`
- `app/Models/Coupon.php`
- `app/Http/Requests/CouponRequest.php`
- `app/Http/Controllers/CouponController.php`

Recommendation: implement marketplace/vendor/category/product scoped coupons with usage limits and audit trail.

## Support Ticket / Chat

Best source: LivaChat and TicketGo.

Useful for support tickets, canned replies, notification templates, OTP, and live chat patterns. Risk is medium; rebuild cleanly.

## Media Manager

Best source: Smartend.

Use `FileManagerController`, `FileController`, `AttachFile`, and media views as reference only. Add strict upload validation.

## SEO Manager

Best source: Smartend.

Use sitemap/content/settings pattern as reference for NeoGiga SEO metadata, canonical URLs, sitemap refresh, and redirects.

## Import/Export

Best source: UltimatePOS and Salesy.

Use their opening-stock/product/purchase import workflows as validation reference, not code.

## Multi-Currency

Best source: Digikash and UltimatePOS.

Adapt currency precision and exchange-rate snapshot ideas for payments, wallet, POS, and vendor payouts.

