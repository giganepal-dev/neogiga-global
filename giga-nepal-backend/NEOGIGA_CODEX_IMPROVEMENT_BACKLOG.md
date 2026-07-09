# NeoGiga Codex Improvement Backlog

## P0 Critical

1. DB production separation mismatch
   - Evidence: Laravel config reports `db=neogiga`, requirement says `neogiga_prod`.
   - Impact: deployment/data-governance risk.
   - Fix: create/verify `neogiga_prod`, migrate/restore safely, update env with rollback plan.
   - Codex safe: yes, with explicit approval.

2. Test command unavailable
   - Evidence: `php artisan test` returns command not defined.
   - Impact: no automated QA gate.
   - Fix: restore test tooling in staging/CI, do not modify production blindly.
   - Codex safe: yes in staging.

3. 501 public/contract endpoints
   - Evidence: AI, add-BOM, POS refund, imports/exports, invoice generation.
   - Impact: broken promised API surface.
   - Fix: implement or remove/hide until ready.
   - Codex safe: yes.

4. Admin auth/RBAC placeholder
   - Evidence: `EnsureAdminToken` documents Phase-0 placeholder; no policies.
   - Impact: security/operations risk.
   - Fix: first-party admin auth/policies/role gates.
   - Codex safe: yes.

## P1 Must Finish Before Launch

- Populate and manage product/brand/vendor/order lifecycle.
- Complete catalog/product detail/search/cart/checkout UI.
- Complete vendor and product approval dashboards.
- Complete inventory idempotency, transfer lifecycle, barcode lookup.
- Complete POS refund/shift closing/cash movement.
- Add health check, backup, queue/scheduler monitoring.
- Implement SEO metadata for product/category pages.

## P2 Growth

- Replace marketing placeholder jobs with real safe-mode implementations.
- Complete GA4/consent-aware frontend analytics.
- Add campaign dashboards from real event data.
- Complete account/order transactional email flows.

## P3 Advanced

- AI/BOM orchestration.
- Wallet/store credit/payment provider abstraction.
- Affiliate fraud prevention and payout workflow.
- Gift card/coupon redemption UI.
- ERP reporting/export polish.

