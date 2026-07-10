# Production Import Blockers

1. Configure `DATABASE_URL` in production without exposing credentials.
2. Replace or extend the ETL loader so it writes to existing canonical NeoGiga tables:
   - `product_brands` / manufacturer model decision
   - `product_categories`
   - `products`
   - `product_specs` / `product_specifications`
   - `product_documents`
   - `marketplace_product_prices` or vendor/distributor offers
   - `inventory_stocks` / regional overlays
3. Add safe metadata contract for every imported product/offer/spec:
   - `source`
   - `source_part_id`
   - `import_batch_id`
   - `source_url`
   - `source_checksum`
   - `imported_at`
   - `review_status`
   - `data_quality_score`
4. Preserve curated fields on conflict. Do not overwrite manual title, SEO, media, LMS, seller, inventory, or AI data.
5. Add advisory lock and canonical dry-run duplicate checks before pilot import.
