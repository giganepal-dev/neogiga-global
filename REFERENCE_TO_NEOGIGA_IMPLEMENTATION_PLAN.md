# 10 — Reference → NeoGiga Implementation Plan (2026-07-12)

Everything below is **rebuild-native** (no reference code copied — see docs #3/#4). Each item ships
only with: schema + service + actor-specific authorization + object/tenant scope + UI + tests, per
the mission's completion rule. The correct guard depends on the actor: admin surfaces use
`admin.user`/`permission:` plus scope, seller surfaces use `seller.web` plus seller/vendor scope,
and customer surfaces use customer session/API ownership checks. Nothing deploys until
`php artisan test` is green and a prod smoke test passes.

## Gate 0 — unblock the platform (BEFORE Release 1)

| # | Task | Why |
|---|---|---|
| 0.1 | Land the PR #11 fix (routes/api.php quote + pcb uuid↔bigint FKs) — running in session task_79162b43 | main cannot boot or `migrate:fresh`; CI is red |
| 0.2 | Merge branch `claude/release-a-cart-auth` (CartService, BOM add-to-cart, **admin.user RBAC gate**, 17 tests) | R1 RBAC + R2 POS both depend on it |
| 0.3 | Merge branch `claude/icon-system` only after BOM/PCB upload hardening is in the branch or lands first: quarantine storage, extension/MIME/size validation, malware-scan hook, safe filename handling, and tests for BOM/Gerber upload rejection paths | mission's UI/icon requirements + PCB intake; upload safety must precede public file intake |
| 0.4 | `composer require phpoffice/phpspreadsheet barryvdh/laravel-dompdf` (approved deps, doc #4) | unblocks importer XLSX + invoice PDF |

## Release 1 — Admin core (default build order: migration→service→tests→UI→smoke; money paths
also require idempotency, audit rows, and service tests before UI work)

1. **Settings store** (`settings` KV + `/admin/settings`, audit rows) — keystone (doc #9).
2. **RBAC completion**: role CRUD/clone UI, 22-role seeder, `role_scopes`
   (marketplace/country/warehouse/org/seller), destructive flag, `role_audit_logs`, permission
   sweep across every admin write route. Policy tests + scope-isolation tests.
3. **Dashboard upgrade**: KPI aggregates (gross/net sales, AOV, low/out-of-stock, top-N lists) +
   `DashboardFilter` (date/marketplace/country/warehouse/brand/category/payment/status). Defer
   seller filtering to Release 2 unless the `order_seller_splits` ownership contract lands in R1.
   Real queries only.
4. **Employee module**: `employees`, `departments`, `designations`, `login_histories`, documents
   (private disk), notes.
5. **Catalog**: brand extras (aliases/authorization/microsite), category tree UI
   (drag-drop/merge/slug-redirect), **typed attribute engine** + variation generator
   (barcode column included).
6. **Importer v1**: generic CSV/XLSX product+category import — dry-run, preview, validation report,
   checkpoint, rollback, error export (extends BomImportParser philosophy; phpspreadsheet).
7. **Orders**: 14-state machine + `order_status_histories` + transition guards; manual order
   creation; refund workflow (request→evidence→approve/reject→liability→audit); invoice PDFs
   (dompdf) + credit notes + packing slips with immutable snapshots (DocumentNumberService).
8. **Promotions**: coupon module + flash/featured/day-deal CRUD **compiling into PricingRuleResolver
   rules**; margin protection via PriceSimulator preview; banner manager (internal URLs only).
9. **Announcements** (doc #9).
→ `RELEASE_1_VALIDATION_REPORT.md` (route:list, migrate --pretend, full suite, policy tests,
   marketplace-isolation tests) then deploy per the safe-deploy runbook (migration→code→config:cache
   last→canaries).

## Release 2 — Seller platform

seller-split schema (`order_seller_splits`, `seller_offers`, `seller_shops`, settlements) →
`seller.web` guard + seller-scope middleware + **isolation tests** → portal (dashboard, products
w/ MPN-match request, orders accept→dispatch, shop mgmt w/ publication moderation + no-external-
contact validation) → settlements/payouts (wallet + ledger) → **POS** per doc #8 → barcode labels
(milon/barcode).

## Release 3 — Customer

session auth + OTP (existing `otp` limiter) + verification → account area (orders/addresses/invoice
download) → wishlist/compare/recently-viewed → return requests → loyalty ledger → internal chat
(conversations over support-ticket model; buyer↔seller, RFQ negotiation, PCB project threads, with
object-level participant authorization, marketplace/thread-type scope, and cross-tenant tests) →
social login (configured providers only) → payment-gateway adapters per marketplace (creds gated).

## Release 4 — Scale & polish

bulk ops sweep (approve/publish/price/stock…), versioned API completion (`/api/v1` catalog→reports
with rate limits + audit logs), translation admin + fallback rules, report exports (CSV/XLSX/PDF),
performance passes, accessibility audit (axe), security hardening (Sanctum evaluation, session
hygiene, and expanded upload scanning coverage beyond the Gate 0 BOM/Gerber baseline).

## Risk register (top 5)

1. **Parallel automation on main** — all work in isolated worktree branches; drift-check before
   merging shared files (established pattern).
2. **PR #11 debris** — Gate 0.1 mandatory; never sideline migrations in a commit.
3. **Money paths (refunds/settlements/POS)** — idempotent commits + audit rows + tests before UI.
4. **RBAC scope regressions** — every scope change ships with isolation tests (mission validation).
5. **Prod deploy hazards** — migration-before-model order, config:cache last, wallet canary
   (`curl -L`), per ops-safety memory.

**Deliverables #11–#16 (release validation reports, final adaptation report, completion matrix) are
produced as each release actually completes — not before (mission: no completion claims without
evidence).**
