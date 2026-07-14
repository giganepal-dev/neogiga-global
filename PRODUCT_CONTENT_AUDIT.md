# Product Content Audit

Generated: 2026-07-13

## Current State

- Product detail pages render name, SKU, MPN, short description, specs, reviews, seller offers, documents, LMS links, alternatives, RFQ, and cart actions.
- SEO title/description fallback exists in Blade templates.
- AI/content rewrite workflow is not yet a dedicated versioned pipeline.

## Gaps

- No complete product rewrite job table/status workflow was found for `pending_rewrite`, `rewritten`, `needs_human_review`, `approved`, and `published` lifecycle.
- Source provenance is present in import tables and newly added product source columns, but public pages do not yet expose structured expandable source references for every item.
- Low-confidence AI content publication gates need explicit admin workflow.

## Next Fixes

1. Add product content rewrite job/history tables.
2. Add source-backed content confidence and approval screens.
3. Generate FAQ/spec copy only from verified source fields or mark as advisory.
