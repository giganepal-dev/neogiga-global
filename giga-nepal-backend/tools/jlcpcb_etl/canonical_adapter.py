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
SOURCE_FILE = "jlcpcb-components.sqlite3"
SOURCE_PAGE_URL = "https://cdfer.github.io/jlcpcb-parts-database/jlcpcb-components.sqlite3"
LICENSE_NOTE = "CDFER repository is MIT licensed; third-party component, datasheet, and image rights are not implied."
REQUIRED_PROVENANCE_FIELDS = (
    "source_name",
    "source_url",
    "source_file",
    "source_page_url",
    "downloaded_at",
    "imported_at",
    "data_year",
    "license_note",
    "confidence_level",
    "original_raw_value",
    "normalized_value",
)
MAX_COMMIT_ROWS = 1000
LOCALIZED_MARKETS = {
    "global": {
        "locale": "en",
        "country": "Global",
        "currency": "USD",
        "domain": "neogiga.com",
        "brand": "NeoGiga",
        "category_title": "Buy {name} Online | Technical Data & RFQ | NeoGiga",
        "product_title": "Buy {mpn} Online | Technical Data & RFQ | NeoGiga",
        "brand_title": "Buy {brand} Components Online | Global Electronics Marketplace | NeoGiga",
        "availability": "RFQ sourcing and quote-only supplier availability estimates",
    },
    "india": {
        "locale": "en-IN",
        "country": "India",
        "currency": "INR",
        "domain": "in.neogiga.com",
        "brand": "NeoGiga India",
        "category_title": "Buy {name} Online in India | Technical Data & RFQ | NeoGiga India",
        "product_title": "Buy {mpn} Online in India | Technical Data & RFQ | NeoGiga India",
        "brand_title": "Buy {brand} Components in India | Technical Data & RFQ | NeoGiga India",
        "availability": "RFQ sourcing and quote-only supplier availability estimates",
    },
    "nepal": {
        "locale": "en-NP",
        "country": "Nepal",
        "currency": "NPR",
        "domain": "np.neogiga.com",
        "brand": "NeoGiga Nepal",
        "category_title": "Buy {name} in Nepal | Technical Data & RFQ | NeoGiga Nepal",
        "product_title": "Buy {mpn} in Nepal | Technical Data & RFQ | NeoGiga Nepal",
        "brand_title": "Buy {brand} Components in Nepal | Technical Data & RFQ | NeoGiga Nepal",
        "availability": "RFQ sourcing and quote-only supplier availability estimates",
    },
}


@dataclass
class AdapterResult:
    import_batch_id: str | None = None
    after_source_id: str | None = None
    last_source_id: str | None = None
    rows_read: int = 0
    rows_transformed: int = 0
    products_inserted: int = 0
    products_updated: int = 0
    brands_inserted: int = 0
    brands_matched: int = 0
    categories_inserted: int = 0
    categories_matched: int = 0
    source_links_created: int = 0
    source_links_updated: int = 0
    source_aliases_created: int = 0
    source_aliases_updated: int = 0
    specifications_created: int = 0
    specifications_updated: int = 0
    offers_created: int = 0
    offers_updated: int = 0
    images_created: int = 0
    skipped: int = 0
    errors: list[dict[str, Any]] = field(default_factory=list)


def slugify(value: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", (value or "").casefold()).strip("-")
    return slug or "item"


def stable_sku(source_part_id: str) -> str:
    return f"NG-{source_part_id}"


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
    def __init__(
        self,
        dsn: str,
        *,
        source_checksum: str | None,
        dry_run: bool = True,
        no_search_index: bool = False,
        no_seo: bool = False,
        source_provenance: dict[str, Any] | None = None,
        import_mode: str = "pilot",
    ) -> None:
        self.dsn = dsn
        self.source_checksum = source_checksum
        self.dry_run = dry_run
        self.no_search_index = no_search_index
        self.no_seo = no_seo
        self.source_provenance = dict(source_provenance or {})
        self.import_mode = import_mode

    def connection_check(self) -> bool:
        with psycopg.connect(self.dsn) as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT 1")
                return cur.fetchone()[0] == 1

    def publish(
        self,
        parts: Iterable[TransformedPart],
        *,
        after_source_id: str | None = None,
        last_source_id: str | None = None,
        source_rows_read: int | None = None,
        transform_errors: list[dict[str, Any]] | None = None,
        run_id: str | None = None,
        resumed_from_batch_id: str | None = None,
    ) -> AdapterResult:
        parts = list(parts)
        transform_errors = list(transform_errors or [])
        rows_read = source_rows_read if source_rows_read is not None else len(parts) + len(transform_errors)
        if rows_read != len(parts) + len(transform_errors):
            raise RuntimeError("Chunk row accounting mismatch; refusing an ambiguous import batch")
        result = AdapterResult(
            after_source_id=after_source_id,
            last_source_id=last_source_id,
            rows_read=rows_read,
            rows_transformed=len(parts),
            skipped=len(transform_errors),
            errors=[
                {"source_part_id": item.get("source_part_id"), "reason": item.get("reason")}
                for item in transform_errors
            ],
        )
        if self.dry_run:
            return result
        if rows_read > MAX_COMMIT_ROWS:
            raise RuntimeError(f"NeoGiga commit chunks cannot exceed {MAX_COMMIT_ROWS} source rows")
        self._validate_write_provenance()
        if rows_read < 1 or not last_source_id:
            raise RuntimeError("Writable chunks require at least one source row and a last source ID")
        with psycopg.connect(self.dsn, row_factory=dict_row) as conn:
            with conn.transaction():
                self._advisory_lock(conn)
                self._require_alias_schema(conn)
                source_id = self._ensure_source(conn)
                batch_id = self._create_batch(
                    conn,
                    source_id,
                    rows_read,
                    after_source_id=after_source_id,
                    last_source_id=last_source_id,
                    run_id=run_id,
                    resumed_from_batch_id=resumed_from_batch_id,
                )
                result.import_batch_id = batch_id
                for item in transform_errors:
                    self._record_transform_error(conn, batch_id, item)
                for part in parts:
                    self._publish_part(conn, part, source_id, batch_id, result)
                self._complete_batch(conn, batch_id, result)
        return result

    def validate_resume_checkpoint(self, import_batch_id: str, source_checksum: str, last_source_id: str) -> None:
        """Tie a local keyset cursor to the completed database batch that committed it."""

        with psycopg.connect(self.dsn, row_factory=dict_row) as conn:
            row = conn.execute(
                "SELECT checksum, status, metadata FROM catalog_import_batches WHERE id = %s",
                (import_batch_id,),
            ).fetchone()
        if not row:
            raise RuntimeError("Checkpoint import batch does not exist; refusing unsafe resume")
        if row["checksum"] != source_checksum:
            raise RuntimeError("Checkpoint batch checksum does not match current source; refusing unsafe resume")
        if row["status"] != "completed":
            raise RuntimeError("Checkpoint import batch is not completed; refusing unsafe resume")
        metadata = row.get("metadata") or {}
        if str(metadata.get("last_source_id") or "") != str(last_source_id):
            raise RuntimeError("Checkpoint cursor does not match its database batch; refusing unsafe resume")

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
            aliases = conn.execute(
                "SELECT count(*) AS count FROM catalog_product_source_aliases WHERE import_batch_id = %s",
                (batch_id,),
            ).fetchone()["count"]
            plan = {
                "batch_id": batch_id,
                "source_links": len(rows),
                "source_aliases": aliases,
                "products_considered": len(rows),
                "dry_run": dry_run,
            }
            if dry_run:
                return plan
            with conn.transaction():
                conn.execute("DELETE FROM catalog_distributor_offers WHERE import_batch_id = %s", (batch_id,))
                conn.execute("DELETE FROM catalog_product_source_aliases WHERE import_batch_id = %s", (batch_id,))
                conn.execute("DELETE FROM catalog_product_sources WHERE import_batch_id = %s", (batch_id,))
                conn.execute("UPDATE catalog_import_batches SET status = 'rolled_back', completed_at = now() WHERE id = %s", (batch_id,))
            return plan

    def _advisory_lock(self, conn) -> None:
        locked = conn.execute("SELECT pg_try_advisory_xact_lock(hashtext('jlcpcb_parts_database_import')) AS locked").fetchone()["locked"]
        if not locked:
            raise RuntimeError("Another JLCPCB import appears to be running")

    def _require_alias_schema(self, conn) -> None:
        row = conn.execute(
            "SELECT to_regclass('public.catalog_product_source_aliases') AS table_name"
        ).fetchone()
        if not row or row["table_name"] is None:
            raise RuntimeError(
                "Required catalog_product_source_aliases migration is missing; refusing a lossy canonical import"
            )

    def _validate_write_provenance(self) -> None:
        if not self.source_checksum:
            raise RuntimeError("Writable imports require a source checksum")
        missing = [
            field
            for field in REQUIRED_PROVENANCE_FIELDS
            if field not in self.source_provenance or self.source_provenance[field] in (None, "")
        ]
        if missing:
            raise RuntimeError(f"Writable imports require batch provenance fields: {', '.join(missing)}")

    def _ensure_source(self, conn) -> int:
        row = conn.execute(
            """
            INSERT INTO catalog_sources (code, name, source_url, license_notes, active, created_at, updated_at)
            VALUES (%s, %s, %s, %s, true, now(), now())
            ON CONFLICT (code) DO UPDATE SET updated_at = now()
            RETURNING id
            """,
            (SOURCE_CODE, SOURCE_NAME, SOURCE_URL, LICENSE_NOTE),
        ).fetchone()
        return row["id"]

    def _create_batch(
        self,
        conn,
        source_id: int,
        rows_read: int,
        *,
        after_source_id: str | None = None,
        last_source_id: str | None = None,
        run_id: str | None = None,
        resumed_from_batch_id: str | None = None,
    ) -> str:
        metadata = {
            **self.source_provenance,
            "mode": self.import_mode,
            "run_id": run_id,
            "resumed_from_batch_id": resumed_from_batch_id,
            "source_checksum": self.source_checksum,
            "after_source_id": after_source_id,
            "last_source_id": last_source_id,
        }
        batch_id = conn.execute(
            """
            INSERT INTO catalog_import_batches (source_id, checksum, status, started_at, rows_read, metadata)
            VALUES (%s, %s, 'running', now(), %s, %s::jsonb)
            RETURNING id
            """,
            (
                source_id,
                self.source_checksum,
                rows_read,
                json.dumps(metadata, default=str),
            ),
        ).fetchone()["id"]
        return str(batch_id)

    def _complete_batch(self, conn, batch_id: str, result: AdapterResult) -> None:
        conn.execute(
            """
            UPDATE catalog_import_batches
            SET status = 'completed', completed_at = now(), rows_inserted = %s, rows_updated = %s, rows_skipped = %s,
                metadata = COALESCE(metadata, '{}'::jsonb) || %s::jsonb
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

    def _record_transform_error(self, conn, batch_id: str, item: dict[str, Any]) -> None:
        conn.execute(
            "INSERT INTO catalog_import_errors (batch_id, source_part_id, reason, raw_record, created_at) VALUES (%s, %s, %s, %s::jsonb, now())",
            (
                batch_id,
                item.get("source_part_id"),
                str(item.get("reason") or "transform failed"),
                json.dumps(item.get("raw_record"), default=str),
            ),
        )

    def _publish_part(self, conn, part: TransformedPart, source_id: int, batch_id: str, result: AdapterResult) -> None:
        brand_id, inserted_brand = self._brand(conn, part)
        result.brands_inserted += int(inserted_brand)
        result.brands_matched += int(not inserted_brand)
        category_id, inserted_categories = self._category(conn, part)
        result.categories_inserted += inserted_categories
        result.categories_matched += max(0, len(CategoryMapper.ancestors(part.category.path)) - inserted_categories)
        product_id, inserted_product, match_strategy = self._product(
            conn, part, brand_id, category_id, source_id, batch_id
        )
        result.products_inserted += int(inserted_product)
        result.products_updated += int(not inserted_product)
        if inserted_product:
            result.images_created += int(self._placeholder_image(conn, part, product_id))
        link_outcome = self._source_link(
            conn,
            part,
            product_id,
            source_id,
            batch_id,
            match_strategy=match_strategy,
        )
        result.source_links_created += int(link_outcome == "created")
        result.source_links_updated += int(link_outcome == "updated")
        result.source_aliases_created += int(link_outcome == "alias_created")
        result.source_aliases_updated += int(link_outcome == "alias_updated")
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
                json.dumps(self._brand_seo_meta(part.manufacturer.display_name)),
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
                    json.dumps(self._category_seo_meta(name, path)),
                ),
            ).fetchone()
            parent_id = row["id"]
            inserted += 1
        return parent_id, inserted

    def _product(
        self,
        conn,
        part: TransformedPart,
        brand_id: int,
        category_id: int | None,
        source_id: int,
        batch_id: str,
    ) -> tuple[int, bool, str]:
        normalized_mpn = part.normalized_mpn
        existing = conn.execute(
            """
            SELECT p.id, p.name, p.short_description, p.description, p.metadata, p.seo_meta
            FROM catalog_product_sources cps
            JOIN products p ON p.id = cps.product_id
            WHERE cps.source_id = %s AND cps.source_part_id = %s
            LIMIT 1
            """,
            (source_id, part.source_part_id),
        ).fetchone()
        match_strategy = "source_part_id"
        if not existing:
            existing = conn.execute(
                """
                SELECT p.id, p.name, p.short_description, p.description, p.metadata, p.seo_meta
                FROM catalog_product_source_aliases alias
                JOIN products p ON p.id = alias.product_id
                WHERE alias.source_id = %s AND alias.source_part_id = %s
                LIMIT 1
                """,
                (source_id, part.source_part_id),
            ).fetchone()
            match_strategy = "existing_source_alias"
        if not existing:
            existing = conn.execute(
                "SELECT id, name, short_description, description, metadata, seo_meta FROM products WHERE sku = %s LIMIT 1",
                (stable_sku(part.source_part_id),),
            ).fetchone()
            match_strategy = "stable_source_sku"
        if not existing:
            existing = conn.execute(
                r"SELECT id, name, short_description, description, metadata, seo_meta FROM products WHERE brand_id = %s AND upper(regexp_replace(coalesce(mpn,''), '\s+', '', 'g')) = %s LIMIT 1",
                (brand_id, normalized_mpn),
            ).fetchone()
            match_strategy = "brand_normalized_mpn"
        metadata = {
            "source": SOURCE_CODE,
            "source_part_id": part.source_part_id,
            "import_batch_id": batch_id,
            "source_url": SOURCE_URL,
            "source_checksum": self.source_checksum,
            "imported_at": datetime.now(timezone.utc).isoformat(),
            "review_status": "pending_review" if part.category.is_unknown else "source_imported_pending_approval",
            "data_quality_score": float(self._quality_score(part)),
            "source_payload_hash": payload_hash(part),
            "last_synced_at": datetime.now(timezone.utc).isoformat(),
        }
        if existing:
            merged = existing.get("metadata") or {}
            merged.setdefault("jlcpcb_import", metadata)
            seo_meta = existing.get("seo_meta") or {}
            if not seo_meta:
                seo_meta = self._product_seo_meta(part, f"{part.manufacturer.display_name} {part.mpn}".strip())
            conn.execute(
                """
                UPDATE products
                SET category_id = COALESCE(category_id, %s),
                    short_description = COALESCE(NULLIF(short_description, ''), %s),
                    description = COALESCE(NULLIF(description, ''), %s),
                    metadata = %s::json,
                    seo_meta = %s::json,
                    updated_at = now()
                WHERE id = %s
                """,
                (category_id, part.description, part.description, json.dumps(merged), json.dumps(seo_meta), existing["id"]),
            )
            return existing["id"], False, match_strategy
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
                json.dumps(self._product_seo_meta(part, name)),
                part.manufacturer.display_name,
                " ".join(self._product_keywords(part)),
                name,
                (part.description or "")[:300],
            ),
        ).fetchone()
        return row["id"], True, "new_product"

    def _source_link(
        self,
        conn,
        part: TransformedPart,
        product_id: int,
        source_id: int,
        batch_id: str,
        *,
        match_strategy: str,
    ) -> str:
        h = payload_hash(part)
        review_status = "pending_review" if part.category.is_unknown else "source_imported_pending_approval"
        existing = conn.execute(
            """
            SELECT id, product_id, import_batch_id
            FROM catalog_product_sources
            WHERE source_id = %s AND source_part_id = %s
            LIMIT 1
            """,
            (source_id, part.source_part_id),
        ).fetchone()
        if existing:
            if int(existing["product_id"]) != int(product_id):
                raise RuntimeError(
                    f"Immutable source link {part.source_part_id} points to a different canonical product"
                )
            conn.execute(
                """
                UPDATE catalog_product_sources
                SET source_url = %s,
                    source_payload_hash = %s,
                    last_synced_at = now(),
                    data_quality_score = %s,
                    review_status = CASE
                      WHEN catalog_product_sources.review_status IN ('approved', 'rejected')
                        THEN catalog_product_sources.review_status
                      ELSE %s
                    END,
                    raw_snapshot = %s::jsonb,
                    updated_at = now()
                WHERE id = %s
                """,
                (
                    SOURCE_URL,
                    h,
                    self._quality_score(part),
                    review_status,
                    json.dumps(part.__dict__, default=str),
                    existing["id"],
                ),
            )
            return "updated"

        canonical = conn.execute(
            """
            SELECT id, source_part_id
            FROM catalog_product_sources
            WHERE product_id = %s AND source_id = %s
            LIMIT 1
            """,
            (product_id, source_id),
        ).fetchone()
        if canonical:
            created = self._source_alias(
                conn,
                part,
                product_id,
                source_id,
                batch_id,
                canonical_product_source_id=canonical["id"],
                canonical_source_part_id=canonical["source_part_id"],
                match_strategy=match_strategy,
            )
            return "alias_created" if created else "alias_updated"

        conn.execute(
            """
            INSERT INTO catalog_product_sources (
              product_id, source_id, source_part_id, import_batch_id, source_url, source_payload_hash,
              imported_at, last_synced_at, data_quality_score, review_status, raw_snapshot, created_at, updated_at
            )
            VALUES (%s, %s, %s, %s, %s, %s, now(), now(), %s, %s, %s::jsonb, now(), now())
            """,
            (
                product_id,
                source_id,
                part.source_part_id,
                batch_id,
                SOURCE_URL,
                h,
                self._quality_score(part),
                review_status,
                json.dumps(part.__dict__, default=str),
            ),
        )
        return "created"

    def _source_alias(
        self,
        conn,
        part: TransformedPart,
        product_id: int,
        source_id: int,
        batch_id: str,
        *,
        canonical_product_source_id: int,
        canonical_source_part_id: str,
        match_strategy: str,
    ) -> bool:
        existing = conn.execute(
            """
            SELECT id, product_id, canonical_product_source_id
            FROM catalog_product_source_aliases
            WHERE source_id = %s AND source_part_id = %s
            LIMIT 1
            """,
            (source_id, part.source_part_id),
        ).fetchone()
        raw_value = json.dumps(part.__dict__, default=str)
        normalized_value = json.dumps(
            {
                "canonical_product_id": product_id,
                "canonical_source_part_id": canonical_source_part_id,
                "normalized_mpn": part.normalized_mpn,
                "manufacturer": part.manufacturer.normalized_name,
            },
            default=str,
        )
        if existing:
            if (
                int(existing["product_id"]) != int(product_id)
                or int(existing["canonical_product_source_id"]) != int(canonical_product_source_id)
            ):
                raise RuntimeError(
                    f"Immutable source alias {part.source_part_id} points to a different canonical product"
                )
            conn.execute(
                """
                UPDATE catalog_product_source_aliases
                SET source_payload_hash = %s,
                    match_strategy = %s,
                    original_raw_value = %s::jsonb,
                    normalized_value = %s::jsonb,
                    raw_snapshot = %s::jsonb,
                    last_synced_at = now(),
                    updated_at = now()
                WHERE id = %s
                """,
                (
                    payload_hash(part),
                    match_strategy,
                    raw_value,
                    normalized_value,
                    raw_value,
                    existing["id"],
                ),
            )
            return False

        provenance = self.source_provenance
        conn.execute(
            """
            INSERT INTO catalog_product_source_aliases (
              canonical_product_source_id, product_id, source_id, source_part_id, import_batch_id,
              source_payload_hash, match_strategy, source_name, source_url, source_file,
              source_page_url, downloaded_at, imported_at, data_year, license_note,
              confidence_level, original_raw_value, normalized_value, raw_snapshot,
              last_synced_at, created_at, updated_at
            )
            VALUES (
              %s, %s, %s, %s, %s,
              %s, %s, %s, %s, %s,
              %s, %s, %s, %s, %s,
              %s, %s::jsonb, %s::jsonb, %s::jsonb,
              now(), now(), now()
            )
            """,
            (
                canonical_product_source_id,
                product_id,
                source_id,
                part.source_part_id,
                batch_id,
                payload_hash(part),
                match_strategy,
                provenance["source_name"],
                provenance["source_url"],
                provenance["source_file"],
                provenance["source_page_url"],
                provenance["downloaded_at"],
                provenance["imported_at"],
                str(provenance["data_year"]),
                provenance["license_note"],
                provenance["confidence_level"],
                raw_value,
                normalized_value,
                raw_value,
            ),
        )
        return True

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
                # Upgrade-only import: a source row may add a missing
                # specification, but it must never replace a value already
                # curated by NeoGiga or another source.
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
            # Preserve the existing document record and its review metadata.
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

    def _placeholder_image(self, conn, part: TransformedPart, product_id: int) -> bool:
        """Give a newly inserted product the existing local NeoGiga fallback.

        The row is additive, uses no supplier image, and cannot replace or
        deactivate any curated media.
        """

        row = conn.execute(
            """
            INSERT INTO product_images (
              product_id, file_path, file_name, mime_type, sort_order,
              is_primary, alt_text, caption, is_active, created_at, updated_at
            )
            SELECT %s, '/images/products/neogiga-component-placeholder.svg',
              'neogiga-component-placeholder.svg', 'image/svg+xml', 1,
              true, %s, 'NeoGiga catalog placeholder image pending product media review.',
              true, now(), now()
            WHERE NOT EXISTS (
              SELECT 1 FROM product_images WHERE product_id = %s AND is_active = true
            )
            RETURNING id
            """,
            (
                product_id,
                f"{part.manufacturer.display_name} {part.mpn} product image"[:255],
                product_id,
            ),
        ).fetchone()

        return row is not None

    def _offer(self, conn, part: TransformedPart, product_id: int, batch_id: str) -> bool:
        offer = part.offer or {}
        row = conn.execute(
            """
            INSERT INTO catalog_distributor_offers (
              product_id, import_batch_id, distributor, sku, price_breaks, stock, currency, fetched_at,
              marketplace_visibility, review_status, metadata, created_at, updated_at
            )
            VALUES (%s, %s, %s, %s, %s::jsonb, %s, %s, %s, %s::jsonb, 'pending_review', %s::jsonb, now(), now())
              ON CONFLICT (distributor, sku) DO NOTHING
              RETURNING true AS inserted
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
        return bool(row and row["inserted"])

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

    def _product_keywords(self, part: TransformedPart) -> list[str]:
        terms = [
            part.mpn,
            part.normalized_mpn,
            part.manufacturer.display_name,
            part.manufacturer.normalized_name,
            part.category.path,
            part.package or "",
            "buy online",
            "RFQ sourcing",
            "quote-only supplier availability",
            "technical data",
            "B2B electronics",
            "electronic component",
            "semiconductor",
            "LCSC",
            "JLCPCB",
            "NeoGiga",
        ]
        for payload in part.attributes.values():
            if isinstance(payload, dict):
                terms.append(str(payload.get("normalized_value") or payload.get("raw_value") or ""))
                terms.append(str(payload.get("normalized_unit") or ""))
        cleaned = []
        seen = set()
        for term in terms:
            value = re.sub(r"\s+", " ", str(term or "")).strip()
            key = value.casefold()
            if value and key not in seen:
                cleaned.append(value)
                seen.add(key)
        return cleaned[:40]

    def _product_seo_meta(self, part: TransformedPart, name: str) -> dict[str, Any]:
        description = part.description or f"{part.manufacturer.display_name} {part.mpn} electronic component."
        keywords = self._product_keywords(part)
        category_name = self._seo_name(part.category.path.split("/")[-1] if part.category.path else "Electronic Components")
        localized = {}
        for market, info in LOCALIZED_MARKETS.items():
            title = info["product_title"].format(mpn=part.mpn)
            localized[market] = {
                "locale": info["locale"],
                "country": info["country"],
                "currency": info["currency"],
                "domain": info["domain"],
                "title": self._truncate(title, 90),
                "description": self._truncate(
                    f"Buy {part.mpn} by {part.manufacturer.display_name} on {info['brand']}. "
                    f"Review-pending catalog data with {info['availability']}. {description}",
                    158,
                ),
                "keywords": self._seo_terms(keywords + [info["country"], info["currency"], info["brand"], "buy online", "RFQ sourcing"]),
                "canonical_path": f"/products/{slugify(f'{name}-{part.source_part_id}')}",
                "hreflang": info["locale"],
            }
        return {
            "robots": "noindex,nofollow",
            "source": SOURCE_CODE,
            "review_status": "pending_review",
            "tags": self._seo_terms(keywords + ["buy online", "RFQ sourcing", "technical data"])[:24],
            "keywords": self._seo_terms(keywords + ["buy online", "RFQ sourcing", "quote-only supplier availability"]),
            "localized": localized,
            "source_notes": "Generated from JLCPCB/LCSC open parts database; hidden until NeoGiga review.",
            "confidence_level": "source_imported",
            "last_updated": datetime.now(timezone.utc).isoformat(),
            "advisory": "Advisory only",
        }

    def _brand_seo_meta(self, brand: str) -> dict[str, Any]:
        keywords = self._seo_terms([brand, "manufacturer", "electronic components", "technical data", "RFQ sourcing", "B2B electronics", "NeoGiga"])
        return {
            "review_status": "pending_review",
            "source": SOURCE_CODE,
            "robots": "noindex,nofollow",
            "keywords": keywords,
            "localized": {
                market: {
                    "locale": info["locale"],
                    "title": self._truncate(info["brand_title"].format(brand=brand), 90),
                    "description": self._truncate(
                        f"Buy {brand} components through {info['brand']} with {info['availability']}. "
                        "Manufacturer catalog data is pending NeoGiga review.",
                        158,
                    ),
                    "keywords": self._seo_terms(keywords + [info["country"], info["currency"], info["brand"]]),
                }
                for market, info in LOCALIZED_MARKETS.items()
            },
        }

    def _category_seo_meta(self, name: str, path: str) -> dict[str, Any]:
        display_name = self._seo_name(name)
        keywords = self._seo_terms([display_name, name, path, "buy online", "technical data", "RFQ sourcing", "electronic components", "engineering marketplace", "B2B electronics", "NeoGiga"])
        return {
            "review_status": "pending_review",
            "source": SOURCE_CODE,
            "robots": "noindex,nofollow",
            "keywords": keywords,
            "localized": {
                market: {
                    "locale": info["locale"],
                    "title": self._truncate(info["category_title"].format(name=display_name), 90),
                    "description": self._truncate(
                        f"Buy {display_name} through {info['brand']} with {info['availability']}. "
                        "Review-pending imported catalog data for engineering and B2B sourcing.",
                        158,
                    ),
                    "keywords": self._seo_terms(keywords + [info["country"], info["currency"], info["brand"]]),
                }
                for market, info in LOCALIZED_MARKETS.items()
            },
        }

    def _seo_name(self, value: str) -> str:
        text = re.sub(r"\s+", " ", str(value or "Electronic Components")).strip()
        if not text:
            return "Electronic Components"
        lower = text.casefold()
        if lower in {"resistor", "resistors"}:
            return "Resistors"
        if lower in {"capacitor", "capacitors"}:
            return "Capacitors"
        if lower in {"mosfet", "mosfets"}:
            return "MOSFETs"
        if lower in {"mlcc smd", "mlcc"}:
            return "MLCC Capacitors"
        return text

    def _seo_terms(self, terms: list[str]) -> list[str]:
        cleaned = []
        seen = set()
        for term in terms:
            value = re.sub(r"\s+", " ", str(term or "")).strip()
            key = value.casefold()
            if value and key not in seen:
                cleaned.append(value)
                seen.add(key)
        return cleaned[:60]

    def _truncate(self, value: str, length: int) -> str:
        value = re.sub(r"\s+", " ", str(value or "")).strip()
        if len(value) <= length:
            return value
        return value[: max(0, length - 1)].rstrip(" |,-") + "…"
