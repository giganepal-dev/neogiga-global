# NeoGiga — Security Gap Report

**Baseline:** Blueprint §40 (Zero Trust), OWASP Top 10 / ASVS L2 · **Date:** 2026-07-06

## Critical

| ID | Finding | Evidence | Impact | Remediation |
|---|---|---|---|---|
| SEC-01 | **No authentication on any endpoint** | `routes/api.php` — zero middleware; `bootstrap/app.php` `withMiddleware` empty | Anyone can call admin imports, checkout, POS refunds, inventory reservation once handlers exist | Sanctum (or JWT) + `auth` middleware; done as placeholder gate this phase; admin routes now token-gated |
| SEC-02 | **Admin import/export publicly routed** with explicit `// TODO: Add auth middleware` | `routes/api.php:119` | Arbitrary bulk data import/export = full data compromise | Gated behind `admin.token` middleware this phase; real RBAC in Phase 1 |
| SEC-03 | **No authorization/RBAC/ABAC** anywhere | No policies, no gates, no roles used | Horizontal+vertical privilege escalation by design | Blueprint §15: RBAC roles + policy checks; placeholder structure added |
| SEC-04 | Handlers missing → unhandled exceptions leak stack traces when `APP_DEBUG=true` | 37 routed methods absent; no `.env` → debug default risk | Info disclosure | `.env.example` ships `APP_DEBUG=false`; stubs return structured 501 |

## High

| ID | Finding | Remediation |
|---|---|---|
| SEC-05 | No rate limiting (login-less today, but vendor `register` is a spam/abuse vector) | `throttle:api` (60/min) + tighter `throttle:20,1` on write endpoints — added |
| SEC-06 | No input validation on any endpoint (no FormRequests) | Validation added to every implemented endpoint this phase |
| SEC-07 | No security headers (CSP, X-Frame-Options, nosniff, Referrer-Policy, HSTS) | `SecurityHeaders` middleware added globally |
| SEC-08 | `composer.lock` missing → unpinned, unauditable dependency tree | Lock committed after install; enable Dependabot/Renovate in CI (backlog) |
| SEC-09 | No secrets baseline: `.env` absent, no `.env.example`, no key | `.env.example` added (no secrets committed); `php artisan key:generate` documented |
| SEC-10 | IDOR surface: route-model bindings (`{order}`, `{sale}`, `{vendor}`, `{import}`) with no ownership scoping | Documented; must be enforced with policies when auth lands (NEXT_PHASE_BACKLOG) |

## Medium

| ID | Finding | Remediation |
|---|---|---|
| SEC-11 | No audit logging for admin/state-changing actions (Blueprint §40.6) | `audit_logs` table exists in legacy IoT set; wire marketplace actions to it in Phase 1 |
| SEC-12 | AI services accept `marketplace_id`/user context implicitly; no guardrail that price/stock come from DB | AI tool layer added with DB-only reads; guardrail note enforced in code comments + contract |
| SEC-13 | No CSRF concern for pure API today, but web landing page forms (newsletter) must be CSRF-protected | Landing page form posts disabled placeholder (mailto/JS stub), CSRF automatic via `web` group when implemented |
| SEC-14 | File upload endpoints planned (imports, vendor documents) with no upload pipeline (type sniffing, AV, size caps) | Backlog item with Blueprint §40.3 requirements |
| SEC-15 | No dependency vulnerability scanning, SAST, or secret scanning | Backlog: GitHub Actions with `composer audit`, Gitleaks, Semgrep |

## Low / informational

- Session driver defaults fine for API-only; set `SESSION_SECURE_COOKIE=true` in production (documented in DEPLOYMENT_NOTES.md).
- Legacy IoT tables (gps_logs, rfid_logs) may hold PII/location data — classify before production (DPDP/GDPR readiness, Blueprint §5).
- `storage/` committed with default perms — ensure web server cannot serve it directly (standard Laravel public-root deploy).

## Verified-good

- ✅ All models use `$fillable` (no `$guarded = []` mass-assignment holes).
- ✅ Eloquent/parameterized queries throughout (no raw SQL found).
- ✅ No secrets, API keys, or credentials found committed anywhere in the repo.

## composer audit (Phase C)

Result recorded in VALIDATION_REPORT.md.
