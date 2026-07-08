# NeoGiga Production Database Cutover Plan

Date: 2026-07-07

## Current State

- Live Laravel path: `/home/neogiga/laravel/current`
- Current production database observed during audit: `neogiga`
- Target production database name from readiness standard: `neogiga_prod`
- No database rename or `.env` change has been performed by this plan.

## Required Approval

Do not run this cutover until the owner approves a maintenance window and confirms the target database name.

## Safe Cutover Steps

1. Put the application in maintenance mode:
   `php artisan down --render="errors::503"`
2. Back up the current database:
   `mysqldump --single-transaction --routines --triggers neogiga > ~/backups/neogiga-before-prod-cutover-$(date +%Y%m%d-%H%M%S).sql`
3. Create the target database if it does not exist:
   `mysql -e "CREATE DATABASE IF NOT EXISTS neogiga_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
4. Restore the backup into `neogiga_prod`.
5. Verify row counts for critical tables before switching traffic.
6. Update only `DB_DATABASE=neogiga_prod` in `/home/neogiga/laravel/current/.env`.
7. Rebuild Laravel config cache:
   `php artisan config:clear && php artisan config:cache`
8. Run production-safe checks:
   `php artisan neogiga:smoke`
9. Bring the application online:
   `php artisan up`
10. Verify `/health`, public home page, admin login, catalog APIs, and checkout-related protected APIs.

## Rollback

1. Put the application back in maintenance mode.
2. Restore `DB_DATABASE=neogiga` in `.env`.
3. Rebuild config cache.
4. Run `php artisan neogiga:smoke`.
5. Bring the application online and investigate the failed cutover separately.
