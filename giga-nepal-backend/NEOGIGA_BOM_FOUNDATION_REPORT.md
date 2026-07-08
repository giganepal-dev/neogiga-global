# NeoGiga BOM Project Commerce Foundation

Date: 2026-07-08

## Scope

- Added an additive BOM/project-commerce migration under `database/migrations/bom`.
- Added BOM project, category, and item models.
- Added public project read, item/availability, price-estimate, authenticated custom build, user build, and add-to-cart conversion APIs.
- Added admin project and item management APIs behind the existing `admin.token` middleware.
- Added services for pricing, availability, cart conversion recording, custom build normalization, alternatives, and LMS-link extension points.

## Safety

- No production migration was executed.
- No seeder was executed.
- No `.env` file was changed.
- No existing table, route, model, or completed module was deleted.
- Pre-change backup exists at `/home/neogiga/backups/bom-foundation-20260708-052256`.

## Pending Activation

Run the BOM migration only after owner approval and a fresh database backup:

```bash
php artisan migrate --path=database/migrations/bom/2026_07_08_052200_create_bom_project_commerce_tables.php
```

Until then, table-backed endpoints return HTTP 503 with a pending-migration message.
