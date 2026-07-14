# Category Taxonomy Audit

Generated: 2026-07-13

## Current State

- Category model/routes/pages exist through `ProductCategory`, `CategoryController`, and localized `/categories/{slug}` routes.
- Public product listing can filter by category.
- Root categories are used in product index navigation.

## Gaps

- Category pages need richer commercial content: unique overview, subcategory summaries, featured brands/manufacturers, FAQs, and stock-aware product families.
- Attribute templates exist in advanced spec tables but need stronger category-level filter definitions.
- Canonical category SEO should be audited across all locale-prefixed routes.

## Next Fixes

1. Add category SEO completeness checks.
2. Add category featured brand/manufacturer sections.
3. Map category filter metadata to spec template fields.
