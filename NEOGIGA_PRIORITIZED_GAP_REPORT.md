# NeoGiga — Prioritized Gap Report

**Date:** 2026-07-07 · Read-only audit. No changes made. Each item: evidence → risk → fix → effort → next command.

---

## P0 — Critical (do first)

### P0-1 · Sitemap advertises 177 category URLs that 404
- **Evidence:** `sitemap.xml` = 178 `<loc>`; `neogiga.com/categories` and `/categories/{slug}` → **404** live.
- **Affected:** neogiga.com (SEO); files exist locally but undeployed: `resources/views/frontend/*`, `app/Http/Controllers/Web/CategoryController.php`, `routes/web.php`.
- **Risk:** Wasted crawl budget, soft-404s, no category pages indexed.
- **Fix:** Deploy the already-built files; `chown neogiga`; `route:clear && route:cache && view:cache`; verify 200/200/404.
- **Effort:** ~15 min (deploy only, no new code).
- **Next command:** `NEOGIGA_FIX_P0_SECURITY_DEPLOYMENT_COMMAND.md`

### P0-2 · Repo is not under version control
- **Evidence:** `.git` is an empty dir; `git status` → "not a repository".
- **Affected:** whole project; deploys are ad-hoc file copies.
- **Risk:** No rollback, no diff, no audit trail; easy to overwrite/regress (already happened once with a migration).
- **Fix:** `git init`, `.gitignore` (vendor, node_modules, .env, storage), initial commit; optional private remote.
- **Effort:** ~15 min.
- **Next command:** `NEOGIGA_FIX_P0_SECURITY_DEPLOYMENT_COMMAND.md`

### P0-3 · Rotate test admin credential
- **Evidence:** admin web login set to `admin@neogiga.com / NeoGiga@Admin2026` during testing.
- **Risk:** Known password on a live admin.
- **Fix:** Set a strong unique password (owner-chosen) via tinker/`SEED_ADMIN_PASSWORD`; confirm login.
- **Effort:** ~5 min.
- **Next command:** `NEOGIGA_FIX_P0_SECURITY_DEPLOYMENT_COMMAND.md`

> No other P0: debug is off, secrets are web-blocked, DB is isolated, admin is gated, SSL valid. **Production is not in an unsafe state.**

---

## P1 — Core marketplace

- **P1-1 Admin CRUD** — dashboard is read-only; needs vendor approval, product approval, category/price/inventory managers, order manager. *Effort: L.* → `NEOGIGA_ADMIN_DASHBOARD_FIX_COMMAND.md`
- **P1-2 Frontend catalog UX** — beyond `/categories`: product-detail, listing/filters, cart/checkout pages. *Effort: L.* → `NEOGIGA_FRONTEND_UIUX_FIX_COMMAND.md`
- **P1-3 Payment adapters + payout** — checkout creates `pending`; needs eSewa/Khalti/FonePay/Stripe/PayPal/COD + state machine + vendor payout. *Effort: L.* → `NEOGIGA_PAYMENT_AFFILIATE_GIFTCARD_FIX_COMMAND.md`
- **P1-4 Order↔inventory** — server-side reserve/deduct + stock ledger on checkout/fulfilment. *Effort: M.* → `NEOGIGA_INVENTORY_POS_LMS_FIX_COMMAND.md`
- **P1-5 Seeder drift (DB-04)** — fix `ProductSeeder`/`VendorSeeder` columns to enable `SEED_DEMO`. *Effort: M.* → `NEOGIGA_IMPLEMENT_MISSING_MARKETPLACE_CORE_COMMAND.md`
- **P1-6 Vendor regional approval flow** — approve/reject + scoped policies. *Effort: M.* → `NEOGIGA_ADMIN_DASHBOARD_FIX_COMMAND.md`
- **P1-7 Persist vhost directives in Virtualmin** — www/admin edits could be regenerated away. *Effort: S.* → `NEOGIGA_FIX_P0_SECURITY_DEPLOYMENT_COMMAND.md`

---

## P2 — Growth

- **P2-1 Transactional email + OTP** (order confirm, verify, OTP login) + queue worker. → `NEOGIGA_SEO_MARKETING_FIX_COMMAND.md`
- **P2-2 Analytics/GA4 + trending** (products/categories/searches, regional). → `NEOGIGA_SEO_MARKETING_FIX_COMMAND.md`
- **P2-3 Newsletter + email campaigns** (subscribe, lists, unsubscribe). → `NEOGIGA_SEO_MARKETING_FIX_COMMAND.md`
- **P2-4 Abandoned-cart + WhatsApp opt-in**. → `NEOGIGA_SEO_MARKETING_FIX_COMMAND.md`
- **P2-5 Import/export as queued jobs** (dry-run, per-row errors, AV/size checks). → `NEOGIGA_IMPLEMENT_MISSING_MARKETPLACE_CORE_COMMAND.md`
- **P2-6 Dashboard charts/reports** (revenue, orders, stock, vendor). → `NEOGIGA_ADMIN_DASHBOARD_FIX_COMMAND.md`

---

## P3 — Advanced

- **P3-1 POS execution** (shift, cash drawer, receipt, stock deduction, refund). → `NEOGIGA_INVENTORY_POS_LMS_FIX_COMMAND.md`
- **P3-2 LMS delivery** (courses/lessons/projects, product links). → `NEOGIGA_INVENTORY_POS_LMS_FIX_COMMAND.md`
- **P3-3 AI BOM orchestrator** (router, tool registry, guardrails, handoff — env-gated, no live keys). → (Phase-2 command TBD)
- **P3-4 Affiliate/referral** (codes, tracking, commission, payout, fraud checks). → `NEOGIGA_PAYMENT_AFFILIATE_GIFTCARD_FIX_COMMAND.md`
- **P3-5 Gift card / coupon / wallet / loyalty**. → `NEOGIGA_PAYMENT_AFFILIATE_GIFTCARD_FIX_COMMAND.md`

---

## Effort legend
S = <1 day · M = 1–3 days · L = 1–2+ weeks.

## Recommended sequencing
**P0 (deploy categories + git init + rotate pw)** → **P1-2/P1-1 (frontend catalog + admin CRUD)** → **P1-3/P1-4 (payments + inventory)** → **P2 growth** → **P3 advanced**.
The P0 block is <1 hour total and removes the only live defect + the biggest ops risk.
