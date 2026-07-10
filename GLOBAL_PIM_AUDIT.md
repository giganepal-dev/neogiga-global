# GLOBAL_PIM_AUDIT (2026-07-10)

Scope: the canonical global product-information layer (manufacturer_id + normalized_mpn identity),
independent of regional pricing/stock. Local-repo audit (see GLOBAL_COMMERCE_AUDIT.md for the
prod-read constraint note).

## Canonical identity model
| Requested table | State | Evidence / gap |
|---|---|---|
| `manufacturers` (dedicated canonical model) | ❌ Missing | NeoGiga uses `product_brands` (id, name, slug, description, is_active, is_featured, sort_order, marketplace_visibility, seo_meta) as the de-facto manufacturer table — **36 rows exist** (from the JLCPCB pilot). It conflates "brand" (marketing) with "manufacturer" (legal entity) — acceptable for now, but the spec's `manufacturer_id + normalized_mpn` canonical key needs a real normalized-MPN index, which is missing. |
| `manufacturer_aliases` | ❌ Missing | No alias/synonym table for manufacturer name variants (e.g. "TI" vs "Texas Instruments Inc."). The JLCPCB canonical adapter has a `slugify()`+`normalized_name` approach in Python but nothing persisted in a dedicated aliases table. |
| `manufacturer_localizations` | ❌ Missing | `product_brands` has no per-marketplace localized name/description. |
| `products.mpn` + normalization | ⚠️ Partial | `products.mpn` exists (plain string column) but there is **no normalized/canonical MPN column or index** — searches would need `regexp_replace` at query time (seen used ad-hoc in the JLCPCB adapter's dedup lookup), which doesn't scale to 10M+ rows per the Phase 19 target. |
| `catalog_product_sources` (provenance) | ✅ Exists, JLCPCB-scoped | `2026_07_10_120000_create_jlcpcb_catalog_provenance_tables.php` — has `source_id`, `source_part_id`, `import_batch_id`, `source_url`, `source_payload_hash`, `imported_at`, `last_synced_at`, `data_quality_score`, `review_status`, `raw_snapshot`. **This is structurally exactly the `product_source_records` the spec asks for** — it just needs generalizing beyond the single JLCPCB source (it already has a `catalog_sources` table designed for multiple sources, so this is mostly a naming/reuse decision, not new engineering). |

## Categories
| Item | State |
|---|---|
| `product_categories` | ✅ Complete — hierarchical (`parent_id`), 177 base + up to 262 with JLCPCB pilot categories, `marketplace_visibility` (json), `seo_meta` (json), `is_active`/`is_featured`/`sort_order`. |
| `category_localizations` | ❌ Missing — no per-marketplace translated category name/description table; `translations` json exists on `countries`/`currencies` but not on `product_categories`. |

## Products / specs / assets
| Requested table | State | Evidence |
|---|---|---|
| `products` | ✅ Complete | rich schema: name, slug, sku, mpn, vendor_sku, type, status, brand_id, category_id, vendor_id, description, short_description, is_featured/is_virtual/is_downloadable, stock_quantity, weight, `marketplace_visibility`, `attributes` (json), `metadata` (json), `seo_meta` (json). |
| `product_localizations` | ❌ Missing | no per-marketplace localized name/description table — `metadata`/`seo_meta` json could carry this short-term but isn't structured for it. |
| `product_specifications` / `product_specs` | ✅ Exists (as `product_specs`) | product_id, name, value, unit, sort_order, is_visible, is_filterable. Untyped (string value) — no `spec_type`/numeric-range support for faceted search. PR#3's `product_specifications`/`specification_groups`/`spec_template_fields` tables also exist (unwired reference code, see prior REFERENCE_INTEGRATION docs) — a more structured alternative already sitting in the codebase. |
| `product_assets` | ❌ Missing | only `product_documents` (generic document_type/file/url table) exists; no dedicated image/CAD/3D-asset table with versioning. |
| `product_datasheets` | ⚠️ Covered by `product_documents` | `document_type='datasheet'` rows in the generic documents table — functionally present, not a dedicated versioned table. |
| `product_compliance` | ❌ Missing | no RoHS/REACH/certification table; `product_generic_suggestions` has a `safety_certification`/`compliance_certification` pair of free-text columns but nothing structured/versioned. |
| `product_lifecycle_events` | ❌ Missing | no NRND/EOL/obsolescence event tracking. |
| `product_relations` | ⚠️ Partial | `product_related_items` (seen referenced in `DashboardController::products()`) covers manual "related products" — not a typed relation (alternative/substitute/accessory/upgrade). |
| `product_lms_links` | ✅ Exists | product ↔ LMS course linkage. |
| `product_ai_knowledge` | ⚠️ Partial | CommerceAI module tables (`commerce_ai_sessions`, recommendation/BOM tables) exist but aren't a per-product structured knowledge table. |
| `product_source_records` | ✅ Exists under a different name | see `catalog_product_sources` above. |
| Version history | ❌ Missing | no product-level version/audit-trail table beyond `updated_at`. |
| Data-quality score | ✅ Exists (JLCPCB-scoped) | `catalog_product_sources.data_quality_score` — generalizable. |
| Review status | ✅ Exists | `products.approval_status`, `products.visibility_status` (used correctly by the JLCPCB pilot: draft/hidden/pending_review). |

## Reviews / Q&A
| Item | State |
|---|---|
| `product_reviews` | ✅ Live (shipped 2026-07-09/10) — rating, title, body, use_case, is_verified_buyer, moderation workflow, admin queue at `/admin/reviews`. |
| Product Q&A | ❌ Missing as a dedicated model — the support-ticket `category='product_qa'` scope (shipped this cycle) covers it functionally via the support API, not a purpose-built Q&A thread. |

## Summary verdict
The **product/category/spec/document/review core is genuinely strong** — better than a from-scratch
build would produce quickly. The real PIM gaps are: (1) no dedicated `manufacturers` + aliases table
separate from marketing `product_brands`, (2) no normalized-MPN index for scale search, (3) no
localization tables for category/product/manufacturer names, (4) no compliance/lifecycle/typed-relation
tables. None of these block Stage 1 (marketplace/routing) work. Recommend generalizing
`catalog_product_sources`/`catalog_sources` (drop the "jlcpcb_" prefix framing) as the canonical
provenance layer rather than building a parallel one — this is a Stage 2+ item, not in this cycle's scope.
