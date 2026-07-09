# NeoGiga Codex API Route Audit

## Summary

- Total routes: 388
- Route cache: working
- Admin route pages: protected by `admin.web` and redirect unauthenticated users to login in previous verification.
- Admin API routes: protected by `admin.token`

## Route Group Counts

- Admin-related: 275
- AI-related: 81
- Marketing: 31
- Analytics: 25
- Cart: 18
- Orders: 17
- LMS: 18
- Inventory: 13
- POS: 9
- Auth: 8
- Payment-named: 1

## Confirmed Placeholder/Broken Routes

- `/api/v1/ai/session`, `/message`, `/build-bom`, `/add-bom-to-cart`, `/create-pos-invoice`: return 501.
- `/api/v1/cart/add-bom`: returns 501.
- `/api/v1/pos/sales/{sale}/refund`: returns 501.
- `/api/v1/admin/imports/*` and `/api/v1/admin/exports/create`: return 501.
- `/api/v1/orders/{order}/invoice`: returns 501.

## Public Write Routes To Review

These may be intentionally public but need abuse controls beyond throttle:
- `POST /api/v1/vendors/register`
- `POST /api/v1/vendors/{vendor}/apply-marketplace`
- `POST /api/v1/affiliate/track`
- `POST /api/v1/analytics/event`
- `POST /api/v1/newsletter/subscribe`
- `POST /api/v1/whatsapp/opt-in`
- AI POST routes, which are public and currently 501

## Duplicate/Compatibility Notes

- Marketing routes are registered both under `/api/*` and `/api/v1/*`. This may be intentional backwards compatibility, but it doubles public/admin surfaces.
- Route list command with `--columns` failed because the option is not available in this Laravel command version.

## Recommended Fixes

1. Remove or protect public AI write routes until implemented.
2. Complete or hide 501 endpoints before launch.
3. Replace admin token gate with first-party admin auth/RBAC policies.
4. Add CAPTCHA/honeypot/IP reputation protection for public writes.
5. Add route tests for every critical route group.

