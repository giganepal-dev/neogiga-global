# SEO Pattern Audit Report

**Date:** 2026-07-10  
**Scope:** NeoGiga Global Commerce SEO Generation  

## Executive Summary

The current SEO generation system has critical issues causing incomplete metadata, inconsistent patterns across marketplaces, incorrect robots directives, and malformed canonical URLs.

## Current Problem Identification

### Example Product Issue
Product: `0.56" Red Light 3 Digits Common Anode LED`

**Current Output (Broken):**
- Title: `0.56 " Red Light 3 Digits Common Anode LED | NeoGiga`
- Description: `Review 0.56 " Red Light 3 Digits Common Anode LED, source-backed specifications, technical details and RFQ availability...`
- Robots: `noindex,nofollow`
- Canonical: `https://neogiga.com/en/products/` (incomplete - missing slug)

**Root Causes Identified:**

1. **Template Location:** SEO templates are scattered across:
   - `config/neogiga_global.php` - basic templates but uses `{mpn}` first which is wrong
   - `app/Services/Seo/GlobalSeoI18nService.php` - hardcodes `noindex,nofollow` on line 56
   - `app/Services/Marketplace/MarketplaceSeoService.php` - marketplace-level only, not product-level
   - No centralized product/category SEO template engine

2. **Robots Issue:** 
   - `GlobalSeoI18nService::productSeo()` returns `'robots' => 'noindex,nofollow'` hardcoded
   - No deterministic indexability rules based on publication status, content completeness

3. **Canonical URL Issue:**
   - Line 55 in `GlobalSeoI18nService.php`: `'canonical' => 'https://neogiga.com/' . $prefix . '/products/' . Str::slug($replacements['{name}'])`
   - Uses product name for slug instead of actual product slug field
   - Does not handle regional domains correctly

4. **Title Pattern Issues:**
   - Current template: `'{mpn} in {country} | {brand} | Local Stock & RFQ Sourcing'`
   - Puts MPN first (bad for branding)
   - Does not follow the requested pattern: `Buy {Product Name} on NeoGiga {Marketplace} | NeoGiga Engineering Marketplace`
   - No handling for duplicate brand names
   - No character length management

5. **Description Pattern Issues:**
   - Current: `'Source {name} through {brand}. Availability, tax, warranty and delivery are confirmed from regional marketplace configuration.'`
   - Does not match requested pattern with warehouse fulfilment phrases
   - No regional differentiation

6. **Missing Features:**
   - No manual override preservation
   - No confidence level tracking
   - No source detail tracking
   - No marketplace-specific fulfilment phrases
   - No warehouse availability awareness
   - No hreflang generation for products
   - No sitemap integration for products/categories

## Database Schema Analysis

### Current `product_seo_meta` Table
Columns from migration `2026_07_10_000800_extend_product_admin_shell_tables.php`:
- `product_id`
- `meta_title`
- `meta_description`
- `canonical_url`
- `robots` (default: `index,follow`)
- `schema_type` (default: `Product`)
- `confidence_level` (default: `manual`)
- `metadata` (JSON)

**Missing Required Columns:**
- `manual_seo_title`
- `manual_seo_description`
- `use_manual_override` (boolean)
- `generated_seo_title`
- `generated_seo_description`
- `last_generated_at`
- `last_manually_edited_at`
- `edited_by`
- `template_version`
- `marketplace_id`
- `locale`
- `approval_status`
- `seo_short_name`
- `source_notes`
- `generation_source`
- `country`
- `warehouse_display_name`
- `stock_state`
- `rfq_availability`
- `source_completeness`
- `publication_status`

### Current `marketplaces` Table
Extended with SEO columns via `2026_07_10_150000_extend_marketplaces_domain_seo_config.php`:
- `seo_title`, `seo_description`, `seo_keywords`, `seo_h1`
- `seo_canonical_url`, `seo_robots`
- `seo_og_*`, `seo_twitter_*`
- `seo_schema_json`
- `indexable`, `sitemap_enabled`, `hreflang_enabled`
- `seo_is_auto_generated`, `seo_last_generated_at`
- `seo_manual_override_fields`

**Missing Required Columns:**
- `seo_marketplace_name` (e.g., "NeoGiga Nepal")
- `seo_site_suffix` (e.g., "NeoGiga Engineering Marketplace")
- `seo_fulfilment_phrase` (e.g., "B2B Sourcing from Nepal Warehouse.")
- `has_local_warehouse` (boolean)
- `warehouse_display_name`

## File Locations Requiring Updates

| File | Purpose | Issue |
|------|---------|-------|
| `config/neogiga_global.php` | SEO templates | Wrong pattern, no regional fulfilment |
| `app/Services/Seo/GlobalSeoI18nService.php` | Product SEO generation | Hardcoded noindex, broken canonical |
| `app/Services/Marketplace/MarketplaceSeoService.php` | Marketplace SEO | Only handles marketplace homepage |
| `app/Models/Product/ProductSeoMeta.php` | Model | Missing required columns |
| `app/Models/Marketplace/Marketplace.php` | Model | Missing fulfilment config |
| `giga-nepal-backend/resources/views/admin/seo.blade.php` | Admin UI | Basic, no preview/character count |
| `giga-nepal-backend/app/Http/Controllers/Api/Admin/AdminConsoleController.php` | API | No product/category SEO endpoints |

## Required Implementation

### Phase 1: Centralized SEO Template Engine
Create services:
- `SeoTemplateService` - main template processor
- `SeoContextResolver` - resolves entity + marketplace context
- `SeoVariableResolver` - resolves template placeholders
- `SeoCanonicalService` - generates correct canonical URLs
- `SeoIndexabilityService` - deterministic robots rules
- `SeoPreviewService` - admin preview generation
- `SeoBackfillService` - bulk regeneration

### Phase 2: Database Migrations
- Extend `product_seo_meta` with manual override fields
- Extend `product_categories` SEO meta
- Extend `marketplaces` with fulfilment configuration
- Create `seo_template_versions` table

### Phase 3: Template Patterns
Implement patterns per specification:
- Global product/category patterns
- Nepal product/category patterns  
- Regional marketplace patterns
- Warehouse-aware fulfilment phrases
- Brand deduplication logic
- MPN handling modes

### Phase 4: Admin UI
- Character counters
- Preview panels
- Manual override controls
- Readable confidence labels
- Marketplace comparison
- Regenerate buttons

### Phase 5: Indexability Rules
Deterministic rules for:
- `index,follow` when published, complete, valid
- `noindex,follow` when incomplete but link-worthy
- `noindex,nofollow` only for blocked/staging

### Phase 6: Hreflang & Sitemap
- Reciprocal hreflang generation
- Sitemap inclusion rules
- Regional page existence checks

### Phase 7: Backfill Commands
Artisan commands:
- `neogiga:seo-audit`
- `neogiga:seo-regenerate-products`
- `neogiga:seo-regenerate-categories`
- `neogiga:seo-validate`
- `neogiga:seo-rebuild-sitemaps`

## Test Requirements

26 test cases defined in specification covering:
1. Global/Nepal/regional title patterns
2. Warehouse fulfilment phrases
3. Category patterns
4. Manual override preservation
5. Character escaping
6. Brand deduplication
7. MPN handling
8. Length warnings
9. Canonical URL correctness
10. Robots rules
11. Hreflang reciprocity
12. SSR metadata
13. Cache invalidation
14. Sitemap rules
15. Dry-run safety
16. Idempotency

## Next Steps

1. Create audit documentation files
2. Implement database migrations
3. Build centralized SEO service classes
4. Update models
5. Create admin UI components
6. Implement backfill commands
7. Write tests
8. Run dry-run backfill
9. Verify output
