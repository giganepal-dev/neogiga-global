# Catalog Media, Brand and SEO Production Deployment

Date: 2026-07-14

## Outcome

NeoGiga release `/home/neogiga/laravel/releases/20260714-140500-catalog-seo-media` is live. The deployment added product media administration, canonical brand pages and governed catalog SEO as an upgrade layer. Existing design, application data, routes and unrelated modules were retained.

## Recovery points

- Backup directory: `/home/neogiga/backups/catalog-seo-media-20260714-135300`
- PostgreSQL custom dump: `neogiga-before.dump`
- Dump SHA-256: `b7fee3f64d489743cee16fa9bcf9c9ab9dc796fd57c1fef1ef8d18f3cc59e586`
- Code archive SHA-256: `96f7c55044cf2ac8ddd96bf381c9b74234f7645897956d61ed186fd0df71ad68`
- `pg_restore --list` validation passed before migration.
- Apache enabled-site files and the prior `.env` were also preserved in the private backup directory.

## Database safety

The only pending migration, `2026_07_14_170000_add_product_media_brand_and_seo_governance`, completed in 0.47 seconds. Production already had the additive governance columns from compatible earlier migrations, so this migration created the append-only `catalog_seo_versions` table and indexes. Its rollback deliberately preserves governance fields and history.

Core counts before and after deployment are identical:

| Entity | Before | After |
|---|---:|---:|
| Products | 73,058 | 73,058 |
| Categories | 441 | 441 |
| Brands | 469 | 469 |
| Product images | 85,392 | 85,392 |
| Customers | 0 | 0 |
| Orders | 2 | 2 |
| Inventory stocks | 76,882 | 76,882 |
| Marketplace prices | 69,880 | 69,880 |

## SEO regeneration

The pre-write dry run found 69,879 generated product changes and 179 generated category changes. It skipped 3,179 product and 262 category manual/locked records. The resumable generated-only command completed across all 73,058 products and 441 categories, producing 73,058 product SEO rows and 70,058 append-only product/category version snapshots. A final dry run reported zero changes.

## Verification

- Local full suite: 172 passed, 11 intentional legacy skips, 771 assertions.
- Scoped Pint, route syntax, `git diff --check`, Blade cache and Vite build passed.
- Production route, view and configuration caches compiled; no migration remains pending.
- `/health`, `/en`, products, categories, `/en/brands`, `/en/brand/sunlord`, sitemap, robots, admin login, public brand API, favicon, icons and placeholder assets return 200.
- `/en/brands/sunlord` returns 301 to `/en/brand/sunlord`.
- Live product and category pages emit the approved title pattern, complete canonical path and eligibility-based robots value.
- Desktop/mobile browser checks found no overflow, broken images or console errors on the homepage, product, category, brand directory and brand detail routes.

## Operational state

Apache, PHP 8.4 FPM and the NeoGiga queue service are active. Marketing and transactional delivery gates remain unchanged; this deployment sent no email and imported no customer data.
