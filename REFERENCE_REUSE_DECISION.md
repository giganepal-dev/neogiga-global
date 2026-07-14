# 4 — Reference Reuse Decision (2026-07-12)

Authoritative decisions for adapting `~/Desktop/reference` into NeoGiga. Derives from docs #1–#3.

## Global decision

**REUSE AS PATTERN ONLY.** Zero file-level copying of the reference's PHP, Blade, JS, CSS, or
compiled assets (EOL stack + proprietary lineage). What crosses over is: feature checklists,
workflow shapes, status vocabularies, schema *concepts*, and UI information-architecture — rebuilt
natively on Laravel 11 + PostgreSQL 16 + NeoGiga services (RBAC middleware, marketplace scope,
DocumentNumberService, ExchangeRateService, PricingRuleResolver, RfqService, BomImport*, icon
component system).

## Approved dependency additions (packages the reference merely uses)

Package reuse means installing maintained upstream packages from Packagist after per-version license,
security-advisory, and transitive-dependency review. It is not source-code reuse from the reference
project.

| Package | Closes which NeoGiga gap | When |
|---|---|---|
| `phpoffice/phpspreadsheet` | XLSX bulk import/export (known BOM + catalog gap) | Release 1 (import engine) |
| `barryvdh/laravel-dompdf` | Invoice/packing-slip/credit-note PDFs | Release 1 (orders) |
| `milon/barcode` | SKU/product/warehouse labels, POS. License note: upstream package is LGPLv3, so legal/license review is required before adoption | Release 2 (POS) |
| `laravel/socialite` | Social login adapters (show only configured providers) | Release 3 (customer auth) |

## Per-domain decisions (what to take / what NOT to take)

1. **Admin dashboard** — take the KPI list + filter set; keep NeoGiga's real-DB queries and
   system-health center. Never port its MySQL query code.
2. **RBAC** — keep NeoGiga's `roles.permissions` + `permission:`/`admin.user` middleware as the
   engine; take only the role-CRUD/clone UI workflow. Add NeoGiga-only concepts the reference lacks:
   marketplace/country/warehouse/seller scopes, destructive-action flag, audit history.
3. **Catalog** — keep NeoGiga's unlimited-depth categories and Postgres schema; take the tree
   drag-drop UX, merge/redirect-slug workflow, and the typed-attribute + variation-generator shape.
4. **Orders/refunds/invoices** — take the status vocabulary (14 states), refund
   request→evidence→approval→liability flow, and immutable invoice-snapshot rule; implement on
   NeoGiga's existing orders/checkout. Seller-split orders come with the seller portal.
5. **Promotions** — NeoGiga's PricingRuleResolver is the engine (stronger than reference's ad-hoc
   discounts). Build flash/featured/day-deal + coupon + banner **surfaces** that compile into
   pricing rules; take usage-limit/stacking/margin-protection concepts from the reference checklist.
6. **Seller portal + POS** — take the whole IA (dashboard, products, orders, shop, POS with hold
   orders/cash sessions/barcode) as pattern; implement seller-scoped with `b2b/vendor` models,
   warehouse inventory (`InventoryStock`), CartService, `seller.web` guard, seller-scoped
   policies/tokens where API access is needed, and admin middleware only for admin POS routes.
7. **Customer surface** — take auth (email/phone/OTP), wishlist/compare, loyalty-ledger workflows;
   implement with NeoGiga session auth + existing wallet/affiliate-ledger idioms. Show nothing that
   isn't configured (mission rule, already applied in the icon header).
8. **Chat** — extend NeoGiga's existing support-ticket message model into conversations
   (buyer↔seller, RFQ negotiation, PCB project) instead of porting `ChattingController`.
9. **Shipping/delivery-man/COD** — pattern only, Release 3+; provider adapters only where
   credentials exist (mission rule), manual fallback otherwise.
10. **REJECTED outright**: install/update wizard, addon/license system, SoftwareUpdate, test
    routes/controllers, CKEditor 4, Bootstrap4/jQuery/Vue2 assets, Passport/Sanctum mix, any
    branding/demo assets.

## Enforcement checklist for every adapted feature (mission §Adaptation, restated)

understand workflow → map NeoGiga models/routes → rebuild with NeoGiga services → add
actor-specific authorization (`admin.user`/`permission:`, `seller.web`, or customer session/API
ownership) → add marketplace/seller/customer scope → add tests → document. A feature that skips
tests or authorization is not "done" (mission completion rule).
