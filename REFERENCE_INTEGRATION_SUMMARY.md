# REFERENCE_INTEGRATION_SUMMARY (2026-07-09)

**Reused (as pattern → native rebuild, DEPLOYED LIVE):**
- Admin **Orders**: `/admin/orders` (KPIs, order-#/status/payment filters, pagination) and
  `/admin/orders/{id}` (items+totals, payments, addresses, tracking, status update with notes,
  **audit timeline** from `order_status_histories`).
- **Invoice print/download**: `/admin/orders/{id}/invoice` — standalone print-CSS sheet, NeoGiga
  branding (navy/cyan/gold), ⌘P → PDF. CSP-safe (zero JS).

**Refactored:** nothing copied verbatim — all rebuilt on NeoGiga models (`Order`, `OrderItem`,
`OrderStatusHistory`, `Product`, `ProductSpec`) and the existing admin design system.

**Used as UI/design reference only:** archive admin layout/tables/badges/filter-bar concepts;
offline-payment and attribute UIs (NeoGiga equivalents already exist).

**Not safe / not sensible to reuse:** archive `.env` + `shop.sql` (credentials/PII — untouched),
AIZ jQuery/Bootstrap assets (CSP-incompatible), Laravel-8 controllers (age), all modules NeoGiga
already has richer (POS, catalog mgmt, sellers, customers, marketing, media, settings, staff,
wallet, coupons, geo, addons). No MyStoreNepal branding anywhere (grep-verified clean).

**Frontend improvements (native — archive had no storefront):**
- `/products` — searchable (name/SKU/**MPN**), category-filterable, paginated grid; stock badges;
  designed empty state (catalog currently has 1 seed product).
- `/products/{slug}` — spec-sheet page: MPN/brand/SKU header, **technical specification table**
  (`product_specs` name/value/unit), regional-stock note, **Bulk RFQ** CTA, **Ask AI Engineer**
  placeholder, B2B-pricing sign-in prompt, LMS-tutorials link, related products, Product JSON-LD
  + canonical.

**Routes added:** admin `GET orders`, `GET orders/{id}`, `GET orders/{id}/invoice`,
`POST orders/{id}/status` (admin.web, throttled, CSRF); public `GET /products`,
`GET /products/{slug}` (named). Permission-key placeholders (`admin.orders.view/update`)
documented pending web-console RBAC.

**Chat improvements:** audited archive chat module → concrete build plan written (backlog #1 in
NEXT_REFERENCE_INTEGRATION_BACKLOG); no code shipped this cycle by design (new tables need
their own tested cycle).

**Missing backend dependencies (blocking future items):** `conversations`/`conversation_messages`
tables (chat), `product_reviews` table (reviews), product pricing/offer layer for public prices,
web-console RBAC wiring for per-permission admin gates.

**Docs produced:** REFERENCE_CODE_AUDIT, REUSABLE_FEATURE_MAP, REFERENCE_UI_COMPONENT_MAP,
INTEGRATION_RISK_REPORT, NEOGIGA_REFERENCE_GAP_ANALYSIS, REFERENCE_INTEGRATION_PLAN,
REFERENCE_INTEGRATION_VALIDATION, NEXT_REFERENCE_INTEGRATION_BACKLOG (+ this file).

**Next phase recommendation:** ship Support Chat (plan §4) as its own cycle, then product
reviews (§5); wire real RFQ form to the quotations API replacing the mailto CTA; load the real
catalog so /products carries inventory.
