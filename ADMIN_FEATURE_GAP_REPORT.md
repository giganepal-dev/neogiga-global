# 5 — Admin Feature Gap Report (2026-07-12)

State legend: **EXISTING · PARTIAL · MISSING · BROKEN · UI-only · API-only · DB-only ·
PERM-missing · TEST-missing · INERT**. `INERT` means implemented or modelled in code but not wired
to an active user-facing surface yet. Reference source is the `~/Desktop/reference` pattern unless noted.
Evidence base: live admin at admin.neogiga.com (Blade), `routes/web.php` admin group, prior
evidence audits. **Caution: `main` is currently BROKEN (PR #11 — routes syntax + uuid/bigint pcb
migrations); the fix session (task_79162b43) must land before any Release 1 work merges.**

## Dashboard

| Capability | State | Notes / recommended implementation |
|---|---|---|
| Business overview, orders, products, categories, sellers, customers counts | EXISTING | real DB queries (mission rule already satisfied); extend KPI set |
| Gross/net sales, AOV, revenue | PARTIAL | order totals exist; add aggregate queries + date-range filter (P1, low risk) |
| COGS / gross-net profit / commissions / settlements | MISSING | needs cost capture at order time + commission ledger (P2, medium) |
| Refunds KPI | PARTIAL (DB-only) | refund fields exist; no workflow (see refunds) |
| RFQs / BOM / PCB project counts | PARTIAL | RFQ+BOM admin pages exist (`/admin/rfqs`, `/admin/bom-imports`); PCB blocked on schema fix |
| Low/out-of-stock, top products/categories/brands/sellers/countries | MISSING | straightforward aggregates over `inventory_stocks`/order items (P1, low) |
| Abandoned carts | EXISTING | `/admin/marketing/abandoned-carts` + recovery report |
| Queue/import/search/API/DB/storage health | EXISTING | automation's system-health control center (283f1d7) — keep, NG-BETTER than reference |
| Filters (date/marketplace/country/seller/warehouse/brand/category/payment/status/customer-type) | PARTIAL | marketplace list filters exist; generalize a `DashboardFilter` query object (P1) |

## RBAC (custom roles)

| Capability | State | Notes |
|---|---|---|
| Role model + permissions JSON + `permission:` middleware | EXISTING | `Role.allows()`, `EnsurePermission` |
| Per-admin token gate | EXISTING (branch) | `admin.user` middleware + 5 tests on `claude/release-a-cart-auth` — **not yet on main** |
| Role CRUD/clone UI | MISSING / BLOCKED | build `/admin/roles` on existing model only after the admin gate and write-policy foundation below is merged (P1, low after unblock) |
| The 22 named roles | MISSING (seed) / BLOCKED | seeder with sensible permission sets only after deny-by-default permissions and scope checks exist. Never seed broad write access before policy coverage (P1 after unblock) |
| Marketplace/country/warehouse/org/seller scopes | MISSING | add `role_scopes` table + scope checks in middleware (P1, medium — touches auth) |
| Destructive-action permission + audit history | MISSING | `requires_destructive` flag + `role_audit_logs` (P1, low) |
| Policy checks on every write route | PARTIAL / PERM-missing | admin web is session `admin.web` (role-gated) but per-permission checks are not uniform; sweep required before role CRUD/seeding. Writes must be deny-by-default, scope-aware, anti-self-escalation protected, and audited (P0/P1) |

## Employee management

MISSING as a module. Users+roles exist; no departments/designations/reporting-manager/login
history/documents. Recommended: `employees` profile table keyed to users + `login_histories`
(P2, low risk, additive).

## Catalog

| Capability | State |
|---|---|
| Categories unlimited depth | EXISTING (parent_id; 177 seeded; admin /categories live) — reference's fixed 3-level is WEAKER, do not copy |
| Category tree drag-drop, merge, slug redirect, import/export | MISSING (UI + jobs) |
| Brand CRUD | EXISTING; aliases/authorization-status/microsite MISSING (additive columns) |
| Typed attributes engine (12 types, groups, filterable/searchable) | MISSING — biggest catalog gap; design `attributes`,`attribute_groups`,`attribute_values`, category assignment (P1, medium) |
| Variations (SKU/barcode/MPN/price/stock per option) | PARTIAL — `product_variants` exists with SKU/price; barcode + option-matrix generator missing |
| Bulk import/export | PARTIAL — JLCPCB pipeline + BOM CSV parser exist; generic product/category importer with dry-run/preview/rollback MISSING; **XLSX blocked until phpspreadsheet added** |
| Reviews + moderation | EXISTING (approve/reject queue, verified flow, tests). Seller reply / helpful votes / abuse reports MISSING (additive) |

## Orders / refunds / invoices / delivery

| Capability | State |
|---|---|
| Order lifecycle | PARTIAL — statuses + admin status/tracking updates exist (`orders/{id}/status`, `/tracking`); full 14-state machine w/ transition rules MISSING |
| Seller/warehouse split | MISSING (Release 2 dependency) |
| Timeline/status history | PARTIAL (rfq has history; orders need `order_status_histories`) |
| Refund request→evidence→approval→liability | MISSING (fields exist, workflow absent) |
| Invoice HTML | EXISTING (`orders/{id}/invoice`); PDF/credit-note/packing slip MISSING (needs dompdf) |
| Immutable invoice snapshots + numbering rules | PARTIAL (DocumentNumberService exists — reuse) |
| Delivery zones/couriers/labels/POD/COD | MISSING; FreightEstimator/ETA exists as foundation; adapters only where creds exist (mission rule) |
| Manual order creation | MISSING |

## Promotions

Engine EXISTS and is stronger than reference (PricingRuleResolver + PriceSimulator, INERT).
Flash/featured/day-deal surfaces, coupon module, banner manager: MISSING — build as thin CRUD
that compiles into pricing rules (P1, medium; margin-protection via simulator preview).

**Top P0/P1 order:** (0) land PR#11 fix + merge `admin.user`; (1) complete deny-by-default
permission checks, scope enforcement, anti-self-escalation rules, and role/scope audit history on
all admin write routes; (2) role CRUD UI + 22-role seeder after those safeguards are enforced;
(3) dashboard KPI/filters; (4) coupons+deals on pricing engine; (5) order status machine + refund
workflow + PDF invoices; (6) typed attributes; (7) generic importer (+XLSX).
