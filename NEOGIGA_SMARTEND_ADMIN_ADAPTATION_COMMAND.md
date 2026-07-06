# NeoGiga Smartend Admin Adaptation Command

Implement a Smartend-inspired admin dashboard in NeoGiga without copying Smartend code.

Rules:
- Audit existing NeoGiga admin routes, controllers, views, middleware, and data first.
- Do not delete or overwrite current modules.
- Create backup before migrations.
- Add incremental migrations only.
- Update `CHANGELOG.md`.
- Preserve `backend.neogiga.com` for APIs and `admin.neogiga.com` for admin UI.

Reference:
- Smartend source root: `/tmp/neogiga-reference-rescan/smartend-/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core`
- Use only as architecture reference for menu/sidebar/settings/CMS/media/SEO patterns.

Build:
- Admin dashboard shell with stable sidebar/menu groups.
- Admin API endpoints for dashboard metrics, marketplace settings, user roles, vendor approvals, product approvals, inventory, orders, POS, marketing, analytics, SEO, and LMS.
- Admin UI pages under `/admin/*` using existing NeoGiga theme.
- Settings modules: marketplace settings, country/currency, SEO metadata, notification settings, import/export settings.
- Media/file manager abstraction with validation, MIME allowlist, size limits, and audit log.
- SEO manager with sitemap status, page metadata, redirects, canonical URLs, and robots controls.
- Permission model compatible with NeoGiga admin/vendor/customer roles.

Verification:
- Route cache passes.
- Admin pages redirect unauthenticated users to login.
- Admin APIs return 401 without token.
- Existing admin pages still work.

