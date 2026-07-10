"""NeoGiga canonical catalog adapter.

This adapter targets the existing Laravel marketplace tables. It intentionally
keeps imported products in review/draft visibility by default.
"""

from __future__ import annotations

import hashlib
import json
import re
from dataclasses import dataclass, field
from datetime import datetime, timezone
from decimal import Decimal
from typing import Any, Iterable

import psycopg
from psycopg.rows import dict_row

from .category_mapper import CategoryMapper
from .transformer import TransformedPart


SOURCE_CODE = "jlcpcb_parts_database"
SOURCE_NAME = "JLCPCB/LCSC open parts database"
SOURCE_URL = "https://github.com/CDFER/jlcpcb-parts-database"


@dataclass
class AdapterResult:
    import_batch_id: str | None = None
    rows_read: int = 0
    products_inserted: int = 0
    products_updated: int = 0
    brands_inserted: int = 0
    brands_matched: int = 0
    categories_inserted: int = 0
    categories_matched: int = 0
    source_links_created: int = 0
    source_links_updated: int = 0
    specifications_created: int = 0
    specifications_updated: int = 0
    offers_created: int = 0
    offers_updated: int = 0
    skipped: int = 0
    errors: list[dict[str, Any]] = field(default_factory=list)


def slugify(value: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", (value or "").casefold()).strip("-")
    return slug or "item"


def stable_sku(source_part_id: str) -> str:
    return f"JLCPCB-{source_part_id}"


def payload_hash(part: TransformedPart) -> str:
    payload = {
        "source_part_id": part.source_part_id,
        "mpn": part.mpn,
        "manufacturer": part.manufacturer.normalized_name,
        "category": part.category.path,
        "description": part.description,
        "package": part.package,
        "datasheet_url": part.datasheet_url,
        "attributes": part.attributes,
        "offer": part.offer,
    }
    return hashlib.sha256(json.dumps(payload, sort_keys=True, default=str).encode("utf-8")).hexdigest()


class NeoGigaCanonicalAdapter:
    def __init__(self, dsn: str, *, source_checksum: str | None, dry_run: bool = True, no_search_index: bool = False, no_seo: bool = False) -> None:
        self.dsn = dsn
        self.source_checksum = source_checksum
        self.dry_run = dry_run
        self.no_search_index = no_search_index
        self.no_seo = no_seo

    def connection_check(self) -> bool:
        with psycopg.connect(self.dsn) as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT 1")
                return cur.fetchone()[0] == 1

    def publish(self, parts: Iterable[TransformedPart]) -> AdapterResult:
        result = AdapterResult()
        parts = list(parts)
        result.rows_read = len(parts)
        if self.dry_run:
            return result
        with psycopg.connect(self.dsn, row_factory=dict_row) as conn:
            with conn.transaction():
                self._advisory_lock(conn)
                source_id = self._ensure_source(conn)
                batch_id = self._create_batch(conn, source_id, len(parts))
                result.import_batch_id = batch_id
                for part in parts:
                    try:
                        self._publish_part(conn, part, source_id, batch_id, result)
                    except Exception as exc:
                        result.skipped += 1
                        result.errors.append({"source_part_id": part.source_part_id, "reason": str(exc)})
                        self._record_error(conn, batch_id, part, str(exc))
                self._complete_batch(conn, batch_id, result)
        return result

    def rollback(self, batch_id: str, *, dry_run: bool = True) -> dict[str, Any]:
        with psycopg.connect(self.dsn, row_factory=dict_row) as conn:
            rows = conn.execute(
                """
                SELECT cps.product_id, p.sku, p.name
                FROM catalog_product_sources cps
                JOIN products p ON p.id = cps.product_id
                WHERE cps.import_batch_id = %s
                """,
                (batch_id,),
            ).fetchall()
            plan = {"batch_id": batch_id, "source_links": len(rows), "products_considered": len(rows), "dry_run": dry_run}
            if dry_run:
                return plan
            with conn.transaction():
                conn.execute("DELETE FROM catalog_distributor_offers WHERE import_batch_id = %s", (batch_id,))
                conn.execute("DELETE FROM catalog_product_sources WHERE import_batch_id = %s", (batch_id,))
                conn.execute("UPDATE catalog_import_batches SET status = 'rolled_back', completed_at = now() WHERE id = %s", (batch_id,))
            return plan

    def _advisory_lock(self, conn) -> None:
        locked = conn.execute("SELECT pg_try_advisory_xact_lock(hashtext('jlcpcb_parts_database_import')) AS locked").fetchone()["locked"]
        if not locked:
            raise RuntimeError("Another JLCPCB import appears to be running")

    def _ensure_source(self, conn) -> int:
        row = conn.execute(
            """
            INSERT INTO catalog_sources (code, name, source_url, license_notes, active, created_at, updated_at)
            VALUES (%s, %s, %s, %s, true, now(), now())
            ON CONFLICT (code) DO UPDATE SET updated_at = now()
            RETURNING id
            """,
            (SOURCE_CODE, SOURCE_NAME, SOURCE_URL, "CDFER repository is MIT licensed; upstream component data is source-provided."),
        ).fetchone()
        return row["id"]

    def _create_batch(self, conn, source_id: int, rows_read: int) -> str:
        batch_id = conn.execute(
            """
            INSERT INTO catalog_import_batches (source_id, checksum, status, started_at, rows_read, metadata)
            VALUES (%s, %s, 'running', now(), %s, %s::jsonb)
            RETURNING id
            """,
            (source_id, self.source_checksum, rows_read, json.dumps({"mode": "pilot"})),
        ).fetchone()["id"]
        return str(batch_id)

    def _complete_batch(self, conn, batch_id: str, result: AdapterResult) -> None:
        conn.execute(
            """
            UPDATE catalog_import_batches
            SET status = 'completed', completed_at = now(), rows_inserted = %s, rows_updated = %s, rows_skipped = %s, metadata = %s::jsonb
            WHERE id = %s
            """,
            (
                result.products_inserted,
                result.products_updated,
                result.skipped,
                json.dumps(result.__dict__, default=str),
                batch_id,
            ),
        )

    def _record_error(self, conn, batch_id: str, part: TransformedPart, reason: str) -> None:
        conn.execute(
            "INSERT INTO catalog_import_errors (batch_id, source_part_id, reason, raw_record, created_at) VALUES (%s, %s, %s, %s::jsonb, now())",
            (batch_id, part.source_part_id, reason, json.dumps(part.__dict__, default=str)),
        )

    def _publish_part(self, conn, part: TransformedPart, source_id: int, batch_id: str, result: AdapterResult) -> None:
        brand_id, inserted_brand = self._brand(conn, part)
        result.brands_inserted += int(inserted_brand)
        result.brands_matched += int(not inserted_brand)
        category_id, inserted_categories = self._category(conn, part)
        result.categories_inserted += inserted_categories
        result.categories_matched += max(0, len(CategoryMapper.ancestors(part.category.path)) - inserted_categories)
        product_id, inserted_product = self._product(conn, part, brand_id, category_id, batch_id)
        result.products_inserted += int(inserted_product)
        result.products_updated += int(not inserted_product)
        created_link = self._source_link(conn, part, product_id, source_id, batch_id)
        result.source_links_created += int(created_link)
        result.source_links_updated += int(not created_link)
        created_specs, updated_specs = self._specs(conn, part, product_id)
        result.specifications_created += created_specs
        result.specifications_updated += updated_specs
        if part.datasheet_url:
            self._datasheet(conn, part, product_id, batch_id)
        if part.offer:
            created_offer = self._offer(conn, part, product_id, batch_id)
            result.offers_created += int(created_offer)
            result.offers_updated += int(not created_offer)

    def _brand(self, conn, part: TransformedPart) -> tuple[int, bool]:
        slug = slugify(part.manufacturer.normalized_name)
        existing = conn.execute("SELECT id FROM product_brands WHERE slug = %s", (slug,)).fetchone()
        if existing:
            return existing["id"], False
        row = conn.execute(
            """
            INSERT INTO product_brands (name, slug, description, is_active, is_featured, sort_order, marketplace_visibility, seo_meta, created_at, updated_at)
            VALUES (%s, %s, %s, true, false, 1000, %s::json, %s::json, now(), now())
            RETURNING id
            """,
            (
                part.manufacturer.display_name,
                slug,
                "Imported manufacturer pending NeoGiga catalog review.",
                json.dumps({"global": True, "nepal": False, "india": False}),
                json.dumps({"review_status": "pending_review", "source": SOURCE_CODE}),
            ),
        ).fetchone()
        return row["id"], True

    def _category(self, conn, part: TransformedPart) -> tuple[int | None, int]:
        parent_id = None
        inserted = 0
        if part.category.is_unknown:
            path = "Uncategorized/Needs Review"
        else:
            path = part.category.path
        for name in [piece.strip() for piece in path.split("/") if piece.strip()]:
            slug = slugify((str(parent_id) + "-" if parent_id else "") + name)
            existing = conn.execute("SELECT id FROM product_categories WHERE slug = %s", (slug,)).fetchone()
            if existing:
                parent_id = existing["id"]
                continue
            row = conn.execute(
                """
                INSERT INTO product_categories (parent_id, name, slug, description, sort_order, is_active, is_featured, marketplace_visibility, seo_meta, created_at, updated_at)
                VALUES (%s, %s, %s, %s, 1000, true, false, %s::json, %s::json, now(), now())
                RETURNING id
                """,
                (
                    parent_id,
                    name,
                    slug,
                    "Imported category pending NeoGiga catalog review.",
                    json.dumps({"global": True, "nepal": False, "india": False}),
                    json.dumps({"review_status": "pending_review", "source": SOURCE_CODE}),
                ),
            ).fetchone()
            parent_id = row["id"]
            inserted += 1
        return parent_id, inserted

    def _product(self, conn, part: TransformedPart, brand_id: int, category_id: int | None, batch_id: str) -> tuple[int, bool]:
        normalized_mpn = part.normalized_mpn
        existing = conn.execute(
            r"SELECT id, name, short_description, description, metadata FROM products WHERE brand_id = %s AND upper(regexp_replace(coalesce(mpn,''), '\s+', '', 'g')) = %s LIMIT 1",
            (brand_id, normalized_mpn),
        ).fetchone()
        metadata = {
            "source": SOURCE_CODE,
            "source_part_id": part.source_part_id,
            "import_batch_id": batch_id,
            "source_url": SOURCE_URL,
            "source_checksum": self.source_checksum,
            "imported_at": datetime.now(timezone.utc).isoformat(),
            "review_status": "pending_review" if part.category.is_unknown else "source_imported_pending_approval",
            "data_quality_score": self._quality_score(part),
            "source_payload_hash": payload_hash(part),
            "last_synced_at": datetime.now(timezone.utc).isoformat(),
        }
        if existing:
            merged = existing.get("metadata") or {}
            merged.setdefault("jlcpcb_import", metadata)
            conn.execute(
                """
                UPDATE products
                SET category_id = COALESCE(category_id, %s),
                    short_description = COALESCE(NULLIF(short_description, ''), %s),
                    description = COALESCE(NULLIF(description, ''), %s),
                    metadata = %s::json,
                    updated_at = now()
                WHERE id = %s
                """,
                (category_id, part.description, part.description, json.dumps(merged), existing["id"]),
            )
            return existing["id"], False
        name = f"{part.manufacturer.display_name} {part.mpn}".strip()
        sku = stable_sku(part.source_part_id)
        slug = slugify(f"{name}-{part.source_part_id}")
        row = conn.execute(
            """
            INSERT INTO products (
              brand_id, category_id, name, slug, sku, mpn, short_description, description,
              type, status, base_price, is_taxable, track_inventory, stock_quantity,
              low_stock_threshold, is_featured, is_virtual, is_downloadable, marketplace_visibility,
              attributes, metadata, seo_meta, manufacturer_name, approval_status, visibility_status,
              search_keywords, meta_title, meta_description, created_at, updated_at
            )
            VALUES (
              %s, %s, %s, %s, %s, %s, %s, %s,
              'simple', 'draft', 0, true, false, 0,
              0, false, false, false, %s::json,
              %s::json, %s::json, %s::json, %s, 'pending_review', 'hidden',
              %s, %s, %s, now(), now()
            )
            RETURNING id
            """,
            (
                brand_id,
                category_id,
                name,
                slug,
                sku,
                part.mpn,
                part.description,
                part.description,
                json.dumps({"global": True, "nepal": False, "india": False}),
                json.dumps(part.attributes),
                json.dumps({"jlcpcb_import": metadata}),
                json.dumps({"robots": "noindex,nofollow", "source": SOURCE_CODE}),
                part.manufacturer.display_name,
                " ".join([part.mpn, part.manufacturer.display_name, part.description or ""]),
                name,
                (part.description or "")[:300],
            ),
        ).fetchone()
        return row["id"], True

    def _source_link(self, conn, part: TransformedPart, product_id: int, source_id: int, batch_id: str) -> bool:
        h = payload_hash(part)
        row = conn.execute(
            """
            INSERT INTO catalog_product_sources (
              product_id, source_id, source_part_id, import_batch_id, source_url, source_payload_hash,
              imported_at, last_synced_at, data_quality_score, review_status, raw_snapshot, created_at, updated_at
            )
            VALUES (%s, %s, %s, %s, %s, %s, now(), now(), %s, %s, %s::jsonb, now(), now())
            ON CONFLICT (source_id, source_part_id)
            DO UPDATE SET product_id = EXCLUDED.product_id, import_batch_id = EXCLUDED.import_batch_id,
              source_payload_hash = EXCLUDED.source_payload_hash, last_synced_at = now(),
              data_quality_score = EXCLUDED.data_quality_score, review_status = EXCLUDED.review_status,
              raw_snapshot = EXCLUDED.raw_snapshot, updated_at = now()
            RETURNING (xmax = 0) AS inserted
            """,
            (
                product_id,
                source_id,
                part.source_part_id,
                batch_id,
                SOURCE_URL,
                h,
                self._quality_score(part),
                "pending_review" if part.category.is_unknown else "source_imported_pending_approval",
                json.dumps(part.__dict__, default=str),
            ),
        ).fetchone()
        return bool(row["inserted"])

    def _specs(self, conn, part: TransformedPart, product_id: int) -> tuple[int, int]:
        created = 0
        updated = 0
        for name, payload in part.attributes.items():
            if name in {"raw", "source_metadata"}:
                continue
            value = payload.get("normalized_value") if isinstance(payload, dict) else None
            unit = payload.get("normalized_unit") if isinstance(payload, dict) else None
            raw_value = payload.get("raw_value") if isinstance(payload, dict) else str(payload)
            existing = conn.execute(
                "SELECT id FROM product_specs WHERE product_id = %s AND name = %s LIMIT 1",
                (product_id, name),
            ).fetchone()
            if existing:
                conn.execute(
                    """
                    UPDATE product_specs
                    SET value = %s, unit = %s, is_visible = true, is_filterable = true, updated_at = now()
                    WHERE id = %s
                    """,
                    (value or raw_value, unit, existing["id"]),
                )
                updated += 1
                continue
            conn.execute(
                """
                INSERT INTO product_specs (product_id, name, value, unit, sort_order, is_visible, is_filterable, created_at, updated_at)
                VALUES (%s, %s, %s, %s, 1000, true, true, now(), now())
                """,
                (product_id, name, value or raw_value, unit),
            )
            created += 1
        return created, updated

    def _datasheet(self, conn, part: TransformedPart, product_id: int, batch_id: str) -> None:
        existing = conn.execute(
            """
            SELECT id FROM product_documents
            WHERE product_id = %s AND document_type = 'datasheet' AND source_url = %s
            LIMIT 1
            """,
            (product_id, part.datasheet_url),
        ).fetchone()
        if existing:
            conn.execute(
                """
                UPDATE product_documents
                SET metadata = %s::json, updated_at = now()
                WHERE id = %s
                """,
                (
                    json.dumps({"source": SOURCE_CODE, "import_batch_id": batch_id, "review_status": "pending_review"}),
                    existing["id"],
                ),
            )
            return
        conn.execute(
            """
            INSERT INTO product_documents (product_id, title, document_type, source_url, status, sort_order, is_public, metadata, is_active, created_at, updated_at)
            VALUES (%s, %s, 'datasheet', %s, 'pending', 100, false, %s::json, true, now(), now())
            """,
            (
                product_id,
                f"Datasheet for {part.mpn}",
                part.datasheet_url,
                json.dumps({"source": SOURCE_CODE, "import_batch_id": batch_id, "review_status": "pending_review"}),
            ),
        )

    def _offer(self, conn, part: TransformedPart, product_id: int, batch_id: str) -> bool:
        offer = part.offer or {}
        row = conn.execute(
            """
            INSERT INTO catalog_distributor_offers (
              product_id, import_batch_id, distributor, sku, price_breaks, stock, currency, fetched_at,
              marketplace_visibility, review_status, metadata, created_at, updated_at
            )
            VALUES (%s, %s, %s, %s, %s::jsonb, %s, %s, %s, %s::jsonb, 'pending_review', %s::jsonb, now(), now())
            ON CONFLICT (distributor, sku)
            DO UPDATE SET product_id = EXCLUDED.product_id, import_batch_id = EXCLUDED.import_batch_id,
              price_breaks = EXCLUDED.price_breaks, stock = EXCLUDED.stock, currency = EXCLUDED.currency,
              fetched_at = EXCLUDED.fetched_at, updated_at = now()
            RETURNING (xmax = 0) AS inserted
            """,
            (
                product_id,
                batch_id,
                offer.get("distributor", "LCSC/JLCPCB"),
                offer.get("sku"),
                json.dumps(offer.get("price_breaks", [])),
                offer.get("stock"),
                offer.get("currency", "USD"),
                offer.get("fetched_at"),
                json.dumps({"global": True, "nepal": False, "india": False}),
                json.dumps({"source": SOURCE_CODE, "source_part_id": part.source_part_id}),
            ),
        ).fetchone()
        return bool(row["inserted"])

    def _quality_score(self, part: TransformedPart) -> Decimal:
        score = Decimal("1.00")
        if part.category.is_unknown:
            score -= Decimal("0.20")
        if not part.datasheet_url:
            score -= Decimal("0.10")
        if not part.package:
            score -= Decimal("0.10")
        if part.warnings:
            score -= Decimal("0.05")
        return max(score, Decimal("0.10"))
