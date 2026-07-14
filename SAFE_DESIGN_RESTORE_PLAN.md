# Safe Design Restore Plan

Date: 2026-07-14

1. Preserve the `e1e14fa` layout and `bae072e` homepage presentation.
2. Add product media controls and gallery markup using existing admin/public cards, fields, buttons, panels, breakpoints, and colors.
3. Add brand list/detail pages using the same approved layout classes; make `/brand/{slug}` canonical and redirect legacy `/brands/{slug}` permanently.
4. Keep all current APIs, relationships, regional stock, offers, products, customers, orders, and imports intact.
5. Add only nullable columns/new governance tables through incremental migrations; take database/code backups before migration.
6. Generate product/category SEO through a centralized marketplace-aware service. Manual or locked records remain active and bulk regeneration updates generated fields only.
7. Verify counts before/after, run focused and full tests/builds, then compare desktop/mobile pages to the selected design baseline before any live deployment.

Rollback is release-based: switch the production release pointer/document root to the previous immutable release and restore the pre-migration database backup only if a migration-related incident requires it. No destructive rollback migration will be used.

## Completion

All seven steps were completed. The prior production release remains available at `/home/neogiga/laravel/releases/20260714-103845-elecforest`, the current release is `/home/neogiga/laravel/releases/20260714-140500-catalog-seo-media`, and the validated recovery set is `/home/neogiga/backups/catalog-seo-media-20260714-135300`.
