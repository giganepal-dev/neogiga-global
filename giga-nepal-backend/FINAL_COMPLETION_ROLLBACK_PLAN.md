# FINAL_COMPLETION_ROLLBACK_PLAN.md

Generated: 2026-07-11

## Scope

Rollback for the admin System Health control-center phase.

## Rollback Steps

1. Restore the backed-up production copies of:
   - `app/Http/Controllers/Admin/DashboardController.php`
   - `resources/views/admin/layout.blade.php`
   - `routes/web.php`
   - `CHANGELOG.md`
2. Remove `resources/views/admin/system-health.blade.php` if it did not exist before the deploy.
3. Run:
   - `php artisan optimize:clear`
   - `php artisan config:cache`
   - `php artisan route:list --path=admin --no-ansi`
   - `php artisan neogiga:smoke --no-ansi`
4. Verify:
   - `https://admin.neogiga.com/admin` redirects to login when unauthenticated.
   - `https://neogiga.com/en` returns successfully.
   - `https://neogiga.com/up` or `/health` returns successfully.

## Database Rollback

No database rollback is required. This phase introduces no migration and does not modify production records.

## Risk

The only expected rollback risk is removing the visible sidebar link and `/admin/system-health` route. Existing admin modules remain unaffected.
