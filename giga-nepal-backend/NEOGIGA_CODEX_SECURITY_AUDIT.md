# NeoGiga Codex Security Audit

## Positive Findings

- Production config reports `debug=false`.
- Admin web routes redirect unauthenticated users to login.
- Admin API routes use `admin.token`.
- Security headers are present in HTTP checks from prior verification.
- Public write routes generally have `throttle:writes`.
- File upload API added MIME and size validation.
- Marketing consent/unsubscribe/suppression structures exist.

## Security Issues / Risks

1. Active DB name is not the required production DB `neogiga_prod`.
2. Admin API token middleware is explicitly a Phase-0 placeholder; no full Sanctum/RBAC/policies.
3. No policies found in `app/Policies`.
4. Test command unavailable, so security regression tests cannot run.
5. Public AI write endpoints are exposed and return 501.
6. Public analytics/newsletter/WhatsApp/vendor/affiliate routes need anti-abuse controls beyond throttling.
7. Payment webhook validation is not complete/verified.
8. POS refund, import/export, and invoice generation return 501.
9. Marketing jobs are placeholders, so promised notifications may silently not happen.
10. No verified health check, backup script, or monitoring report.

## Secrets / Env

- `.env.example` only was inspected. It does not contain real secrets.
- `.env.example` still defaults DB name to `neogiga`, not `neogiga_prod`.

## Recommended Security Fixes

- Move production to `neogiga_prod` or confirm and document why `neogiga` is intentionally production.
- Replace admin token with user-based admin auth, permissions, and policies.
- Add tests for auth, admin protection, public write throttles, upload validation, checkout, inventory, payment placeholders.
- Disable or protect 501 public AI routes.
- Add webhook signature verification before any live provider.
- Add idempotency keys to all financial and stock mutation endpoints.

