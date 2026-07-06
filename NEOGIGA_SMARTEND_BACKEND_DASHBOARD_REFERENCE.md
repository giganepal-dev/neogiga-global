# Smartend Backend Dashboard Reference

Source root: `/tmp/neogiga-reference-rescan/smartend-/codecanyon-19184332-smartend-laravel-admin-dashboard/smartend/core`

## Useful Files

Routes:
- `routes/dashboard.php`
- `routes/custom.php`
- `routes/apis.php`
- `routes/web.php`

Controllers:
- `app/Http/Controllers/Dashboard/FileController.php`
- `app/Http/Controllers/Dashboard/FileManagerController.php`
- `app/Http/Controllers/Dashboard/WebmasterSettingsController.php`
- `app/Http/Controllers/Dashboard/UsersController.php`
- `app/Http/Controllers/Dashboard/WebmailsController.php`
- `app/Http/Controllers/SiteMapController.php`
- `app/Http/Controllers/LanguageController.php`
- `app/Http/Controllers/APIs/APIsController.php`

Models/schema:
- `app/Models/Permissions.php`
- `app/Models/WebmasterSetting.php`
- `app/Models/Section.php`
- `app/Models/Topic.php`
- `app/Models/Menu.php`
- `app/Models/AttachFile.php`
- `app/Models/Webmail.php`
- `database/migrations/2020_09_11_190632_create_webmaster_settings_table.php`
- `database/migrations/2020_09_11_190633_create_webmaster_sections_table.php`
- `database/migrations/2020_09_11_190643_create_settings_table.php`
- `database/migrations/2020_09_11_190650_create_menus_table.php`
- `database/migrations/2020_09_11_190704_create_attach_files_table.php`

Views:
- `resources/views/dashboard.blade.php`
- `resources/views/dashboard/**`
- `resources/views/frontEnd/sitemap.blade.php`
- `resources/views/frontEnd/search.blade.php`

## Patterns Worth Adapting

- Admin dashboard route separation in `routes/dashboard.php`.
- Settings grouped into webmaster/global settings, language, menus, and sections.
- Media/file manager separation from business modules.
- CMS section/topic model for configurable content.
- SEO and sitemap controllers as admin-managed content services.
- Dashboard layout with module sidebar and reusable partials.

## What Not To Use Directly

- Do not copy Smartend Blade, controllers, migrations, or assets directly.
- Do not reuse commercial package code without license confirmation.
- Do not import installer, `.env`, credentials, demo SQL, or old auth flows.
- Avoid bringing Smartend's CMS-first database shape into NeoGiga marketplace modules.

## Laravel 11 Refactor Needed

- Rebuild as API-first admin services under `backend.neogiga.com/api/v1/admin/*`.
- Keep `admin.neogiga.com` as the authenticated admin UI shell.
- Use NeoGiga's existing admin auth/token middleware and PostgreSQL conventions.
- Store dashboard menu definitions as code/config plus optional database overrides.
- Use policies/permissions for marketplace, vendor, product, inventory, POS, marketing, LMS, SEO, and settings.

