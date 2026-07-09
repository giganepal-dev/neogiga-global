# INTEGRATION_RISK_REPORT — archive reuse (2026-07-09)

| Risk | Severity | Mitigation |
|---|---|---|
| Copying archive `.env` (live MyStoreNepal DB/mail/payment creds) | CRITICAL | Never opened/copied; excluded from all operations |
| Importing `shop.sql` (production customer data) | CRITICAL | Never imported; PII stays in the archive |
| License: CodeCanyon commercial code copied verbatim | HIGH | Purchase codes present (user-owned). We copy **no code verbatim** anyway — patterns only, native rebuilds; nothing redistributable created |
| Old branding leakage (MyStoreNepal strings/logos/site-verification files) | MEDIUM | Nothing copied from archive `public/`; all new UI uses NeoGiga branding/palette; grep check in validation |
| Laravel 8 / PHP 7-era code pasted into Laravel 11 / PHP 8.4 | HIGH | Native rebuilds against NeoGiga models — no archive PHP is executed |
| jQuery/Bootstrap assets breaking CSP (`script-src 'self'`) | HIGH | No archive JS/CSS reused; SSR + design-system CSS only |
| Schema collision (archive's `orders`, `conversations`, etc. vs NeoGiga's) | HIGH | No archive migrations run. New admin uses NeoGiga's existing tables; future chat/reviews tables get NEW guarded additive migrations |
| Admin routes without auth | HIGH | All new admin routes inside the existing `admin.web` session-gated group; POSTs throttled + CSRF |
| Unsafe SQL / injection | MEDIUM | Query-builder/Eloquent only; status values whitelisted against the DB enum |
| Destructive actions | MEDIUM | No delete endpoints added; order status changes are logged to `order_status_histories` (audit trail) |
| Prod deploy risk (shared files: web.php, DashboardController, layout) | MEDIUM | Established procedure: pre-deploy drift check vs prod, config:cache last, wallet-canary + smoke tests after |
| Frontend SEO regressions | LOW | New public pages SSR with meta/canonical; no changes to existing pages |
