from __future__ import annotations

import json
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import psycopg
from psycopg.rows import dict_row

from .marketplaces import localized_overlays
from .models import CatalogProductCandidate, SourceManifest
from .normalization import canonical_identity, normalize_mpn, payload_checksum, slugify, stable_sku


@dataclass
class ImportResult:
    batch_id: str | None = None
    rows_read: int = 0
    products_inserted: int = 0
    products_updated: int = 0
    products_published: int = 0
    products_pending_review: int = 0
    manufacturers: set[str] = field(default_factory=set)
    brands: set[str] = field(default_factory=set)
    categories: set[str] = field(default_factory=set)
    images_imported: int = 0
    images_skipped: int = 0
    seo_pages_generated: int = 0
    localization_coverage: dict[str, int] = field(default_factory=dict)
    duplicate_count: int = 0
    errors: list[dict[str, Any]] = field(default_factory=list)

    def to_report(self) -> dict[str, Any]:
        payload = self.__dict__.copy()
        payload["manufacturers"] = sorted(self.manufacturers)
        payload["brands"] = sorted(self.brands)
        payload["categories"] = sorted(self.categories)
        return payload


class CanonicalCatalogWriter:
    def __init__(
        self,
        dsn: str,
        *,
        source: SourceManifest,
        marketplaces: dict[str, dict[str, str]],
        dry_run: bool = True,
        publish_threshold: float = 0.90,
    ) -> None:
        self.dsn = dsn
        self.source = source
        self.marketplaces = marketplaces
        self.dry_run = dry_run
        self.publish_threshold = publish_threshold

    def import_products(self, products: list[CatalogProductCandidate]) -> ImportResult:
        result = ImportResult(rows_read=len(products))
        if self.dry_run:
            self._simulate(products, result)
            return result
        with psycopg.connect(self.dsn, row_factory=dict_row) as conn:
            with conn.transaction():
                self._lock(conn)
                source_id = self._ensure_source(conn)
                batch_id = self._create_batch(conn, source_id, len(products))
                result.batch_id = str(batch_id)
                for product in products:
                    try:
                        product_id, inserted = self._upsert_product(conn, product, source_id, str(batch_id), result)
                        result.products_inserted += int(inserted)
                        result.products_updated += int(not inserted)
                        self._upsert_source_link(conn, product, source_id, product_id, str(batch_id))
                        self._upsert_specs(conn, product_id, product)
                        self._upsert_documents(conn, product_id, product, str(batch_id))
                        self._upsert_images(conn, product_id, product)
                    except Exception as exc:
                        result.errors.append({"source_part_id": product.source_part_id, "mpn": product.mpn, "reason": str(exc)})
                self._complete_batch(conn, str(batch_id), result)
        return result

    def _simulate(self, products: list[CatalogProductCandidate], result: ImportResult) -> None:
        for product in products:
            self._collect_rollups(product, result)
            if product.quality_score >= self.publish_threshold:
                result.products_published += 1
            else:
                result.products_pending_review += 1
            for code in self.marketplaces:
                result.localization_coverage[code] = result.localization_coverage.get(code, 0) + 1
            result.seo_pages_generated += len(self.marketplaces) + 1
            result.images_skipped += len([image for image in product.images if image.original_url and not image.local_path])

    def _lock(self, conn) -> None:
        locked = conn.execute("SELECT pg_try_advisory_xact_lock(hashtext(%s)) AS locked", (f"catalog_import_pipeline:{self.source.code}",)).fetchone()["locked"]
        if not locked:
            raise RuntimeError(f"Another import for {self.source.code} is running")

    def _ensure_source(self, conn) -> int:
        row = conn.execute(
            """
            INSERT INTO catalog_sources (code, name, source_url, license_notes, active, created_at, updated_at)
            VALUES (%s, %s, %s, %s, true, now(), now())
            ON CONFLICT (code) DO UPDATE SET name = EXCLUDED.name, source_url = EXCLUDED.source_url, license_notes = EXCLUDED.license_notes, updated_at = now()
            RETURNING id
            """,
            (self.source.code, self.source.name, self.source.source_url, self.source.license_note),
        ).fetchone()
        return row["id"]

    def _create_batch(self, conn, source_id: int, rows_read: int) -> str:
        return conn.execute(
            """
            INSERT INTO catalog_import_batches (source_id, checksum, status, started_at, rows_read, metadata)
            VALUES (%s, %s, 'running', now(), %s, %s::jsonb)
            RETURNING id
            """,
            (source_id, None, rows_read, json.dumps({"pipeline": "catalog_import_pipeline", "source_code": self.source.code})),
        ).fetchone()["id"]

    def _upsert_product(self, conn, product: CatalogProductCandidate, source_id: int, batch_id: str, result: ImportResult) -> tuple[int, bool]:
        self._collect_rollups(product, result)
        brand_id = self._brand(conn, product)
        category_id = self._category(conn, product)
        normalized_mpn = normalize_mpn(product.mpn)
        existing = conn.execute(
            r"SELECT id, metadata, seo_meta FROM products WHERE brand_id = %s AND upper(regexp_replace(coalesce(mpn,''), '[\s\-_\/]+', '', 'g')) = %s LIMIT 1",
            (brand_id, normalized_mpn),
        ).fetchone()
        overlays = localized_overlays(product.name, product.mpn, product.category.split("/")[-1], self.marketplaces)
        seo_meta = {
            "source": self.source.code,
            "robots": "index,follow" if product.quality_score >= self.publish_threshold else "noindex,nofollow",
            "quality_score": product.quality_score,
            "localized": overlays,
            "structured_data": "Product",
            "source_notes": "Generated from licensed source feed; advisory only until marketplace review.",
            "confidence_level": product.provenance.get("confidence_level"),
            "last_updated": datetime.now(timezone.utc).isoformat(),
            "advisory": "Advisory only",
        }
        metadata = {
            "production_catalog_import": {
                "source": self.source.code,
                "source_part_id": product.source_part_id,
                "import_batch_id": batch_id,
                "source_url": product.source_url,
                "source_checksum": payload_checksum(product.raw),
                "imported_at": datetime.now(timezone.utc).isoformat(),
                "review_status": "approved" if product.quality_score >= self.publish_threshold else "pending_review",
                "data_quality_score": product.quality_score,
                "license": product.source_license,
                "provenance": product.provenance,
            }
        }
        status = "approved" if product.quality_score >= self.publish_threshold else "approved"
        visibility = "marketplace_only" if product.quality_score >= self.publish_threshold else "hidden"
        if existing:
            merged = existing.get("metadata") or {}
            merged.update(metadata)
            conn.execute(
                """
                UPDATE products
                SET category_id = COALESCE(category_id, %s), short_description = COALESCE(NULLIF(short_description, ''), %s),
                    attributes = %s::json, metadata = %s::json, seo_meta = %s::json, updated_at = now()
                WHERE id = %s
                """,
                (category_id, product.short_description, json.dumps(product.parametric_attributes or product.technical_specs), json.dumps(merged), json.dumps(seo_meta), existing["id"]),
            )
            result.products_pending_review += int(product.quality_score < self.publish_threshold)
            result.products_published += int(product.quality_score >= self.publish_threshold)
            return existing["id"], False
        sku = product.sku or stable_sku(product.manufacturer, product.mpn)
        slug = slugify(f"{product.manufacturer}-{product.mpn}-{product.source_part_id}")
        row = conn.execute(
            """
            INSERT INTO products (
                brand_id, category_id, name, slug, sku, mpn, short_description, description, type, status,
                base_price, is_taxable, track_inventory, stock_quantity, low_stock_threshold, is_featured,
                is_virtual, is_downloadable, marketplace_visibility, attributes, metadata, seo_meta,
                manufacturer_name, approval_status, visibility_status, search_keywords, meta_title,
                meta_description, created_at, updated_at
            )
            VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, 'simple', %s,
                0, true, false, 0, 0, false, false, false, %s::json, %s::json, %s::json, %s::json,
                %s, %s, %s, %s, %s, %s, now(), now()
            )
            RETURNING id
            """,
            (
                brand_id, category_id, product.name, slug, sku, product.mpn, product.short_description, product.short_description,
                status, json.dumps({"global": True}), json.dumps(product.parametric_attributes or product.technical_specs),
                json.dumps(metadata), json.dumps(seo_meta), product.manufacturer,
                "approved" if product.quality_score >= self.publish_threshold else "pending_review", visibility,
                " ".join([product.name, product.mpn, product.manufacturer, product.category]), seo_meta["localized"].get("global", {}).get("seo_title"),
                seo_meta["localized"].get("global", {}).get("meta_description"),
            ),
        ).fetchone()
        result.products_pending_review += int(product.quality_score < self.publish_threshold)
        result.products_published += int(product.quality_score >= self.publish_threshold)
        return row["id"], True

    def _brand(self, conn, product: CatalogProductCandidate) -> int:
        name = product.brand or product.manufacturer
        slug = slugify(name)
        row = conn.execute(
            """
            INSERT INTO product_brands (name, slug, description, is_active, is_featured, sort_order, marketplace_visibility, seo_meta, created_at, updated_at)
            VALUES (%s, %s, %s, true, false, 1000, %s::json, %s::json, now(), now())
            ON CONFLICT (slug) DO UPDATE SET updated_at = now()
            RETURNING id
            """,
            (name, slug, f"{name} catalog imported from licensed source.", json.dumps({"global": True}), json.dumps({"source": self.source.code, "robots": "noindex,nofollow"})),
        ).fetchone()
        return row["id"]

    def _category(self, conn, product: CatalogProductCandidate) -> int | None:
        parent_id = None
        for piece in [part.strip() for part in product.category.split("/") if part.strip()]:
            slug = slugify((str(parent_id) + "-" if parent_id else "") + piece)
            row = conn.execute(
                """
                INSERT INTO product_categories (parent_id, name, slug, description, sort_order, is_active, is_featured, marketplace_visibility, seo_meta, created_at, updated_at)
                VALUES (%s, %s, %s, %s, 1000, true, false, %s::json, %s::json, now(), now())
                ON CONFLICT (slug) DO UPDATE SET updated_at = now()
                RETURNING id
                """,
                (parent_id, piece, slug, f"{piece} catalog imported from licensed source.", json.dumps({"global": True}), json.dumps({"source": self.source.code, "robots": "noindex,nofollow"})),
            ).fetchone()
            parent_id = row["id"]
        return parent_id

    def _upsert_source_link(self, conn, product: CatalogProductCandidate, source_id: int, product_id: int, batch_id: str) -> None:
        conn.execute(
            """
            INSERT INTO catalog_product_sources (
                product_id, source_id, source_part_id, import_batch_id, source_url, source_payload_hash,
                imported_at, last_synced_at, data_quality_score, review_status, raw_snapshot, created_at, updated_at
            )
            VALUES (%s, %s, %s, %s, %s, %s, now(), now(), %s, %s, %s::jsonb, now(), now())
            ON CONFLICT (source_id, source_part_id)
            DO UPDATE SET product_id = EXCLUDED.product_id, import_batch_id = EXCLUDED.import_batch_id,
                source_payload_hash = EXCLUDED.source_payload_hash, data_quality_score = EXCLUDED.data_quality_score,
                review_status = CASE WHEN catalog_product_sources.review_status IN ('approved', 'rejected') THEN catalog_product_sources.review_status ELSE EXCLUDED.review_status END,
                raw_snapshot = EXCLUDED.raw_snapshot, last_synced_at = now(), updated_at = now()
            """,
            (
                product_id, source_id, product.source_part_id, batch_id, product.source_url, payload_checksum(product.raw),
                product.quality_score, "approved" if product.quality_score >= self.publish_threshold else "pending_review",
                json.dumps(product.provenance, default=str),
            ),
        )

    def _upsert_specs(self, conn, product_id: int, product: CatalogProductCandidate) -> None:
        for name, value in {**product.technical_specs, **product.parametric_attributes}.items():
            conn.execute(
                """
                INSERT INTO product_specs (product_id, name, value, unit, sort_order, is_visible, is_filterable, created_at, updated_at)
                VALUES (%s, %s, %s, null, 1000, true, true, now(), now())
                ON CONFLICT DO NOTHING
                """,
                (product_id, str(name)[:255], json.dumps(value, default=str) if isinstance(value, (dict, list)) else str(value)),
            )

    def _upsert_documents(self, conn, product_id: int, product: CatalogProductCandidate, batch_id: str) -> None:
        if not product.datasheet_url:
            return
        conn.execute(
            """
            INSERT INTO product_documents (product_id, title, document_type, source_url, status, sort_order, is_public, metadata, is_active, created_at, updated_at)
            VALUES (%s, %s, 'datasheet', %s, 'pending', 100, false, %s::json, true, now(), now())
            ON CONFLICT DO NOTHING
            """,
            (product_id, f"Datasheet for {product.mpn}", product.datasheet_url, json.dumps({"source": self.source.code, "import_batch_id": batch_id, "license": product.source_license})),
        )

    def _upsert_images(self, conn, product_id: int, product: CatalogProductCandidate) -> None:
        for image in product.images:
            if not image.redistribution_allowed or not image.local_path or not Path(image.local_path).exists():
                continue
            path = Path(image.local_path)
            conn.execute(
                """
                INSERT INTO product_images (product_id, file_path, file_name, mime_type, file_size, sort_order, is_primary, alt_text, caption, is_active, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, 100, false, %s, %s, true, now(), now())
                ON CONFLICT DO NOTHING
                """,
                (product_id, str(path), path.name, "image/webp" if path.suffix == ".webp" else "image/jpeg", path.stat().st_size, image.alt_text, image.caption),
            )

    def _complete_batch(self, conn, batch_id: str, result: ImportResult) -> None:
        conn.execute(
            """
            UPDATE catalog_import_batches
            SET status = 'completed', completed_at = now(), rows_inserted = %s, rows_updated = %s, rows_skipped = %s, metadata = %s::jsonb
            WHERE id = %s
            """,
            (result.products_inserted, result.products_updated, len(result.errors), json.dumps(result.to_report(), default=str), batch_id),
        )

    def _collect_rollups(self, product: CatalogProductCandidate, result: ImportResult) -> None:
        result.manufacturers.add(product.manufacturer)
        result.brands.add(product.brand or product.manufacturer)
        result.categories.add(product.category)
        for code in self.marketplaces:
            result.localization_coverage[code] = result.localization_coverage.get(code, 0) + 1
        result.seo_pages_generated += len(self.marketplaces) + 1
        result.images_imported += len([image for image in product.images if image.local_path and image.redistribution_allowed])
        result.images_skipped += len([image for image in product.images if not image.local_path or not image.redistribution_allowed])

