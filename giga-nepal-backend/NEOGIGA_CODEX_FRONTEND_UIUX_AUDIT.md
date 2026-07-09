# NeoGiga Codex Frontend / Admin / UI Audit

## Existing UI

- Public homepage exists and is routed at `/`.
- Public LMS pages exist: `/learn`, `/learn/projects/{slug}`.
- Admin dashboard exists under `/admin`.
- Admin pages exist for categories, products, marketplaces, vendors, users, LMS, inventory, POS, marketing, settings, media, SEO.
- Admin UI has responsive shell/sidebar and empty states.

## Missing Or Incomplete Public UI

- Category listing page.
- Product listing/search page.
- Product detail page.
- Cart page.
- Checkout page.
- Vendor dashboard.
- POS cashier screen/modal.
- AI BOM builder UI.
- Marketplace/country/currency selector beyond homepage placeholder.
- Public coupon/gift-card redemption UI.

## UI/UX Risks

- Public frontend is mainly landing + LMS, not a full marketplace.
- Many admin pages are dashboards/tables, not full create/edit workflows.
- POS is admin overview, not a cashier-grade POS screen.
- SEO metadata handling is foundation-level; dynamic product/category meta needs completion.

## Recommended Next UI Work

1. Build public catalog/search/product detail.
2. Build cart/checkout using existing API.
3. Build vendor dashboard after RBAC hardening.
4. Build POS cashier screen after refund/idempotency and barcode lookup are complete.
5. Add loading/error/empty states to all public commerce flows.

