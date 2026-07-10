"""PostgreSQL connectivity and idempotent batch loading."""

from __future__ import annotations

import json
from collections.abc import Sequence
from datetime import datetime, timezone
from typing import Any

import psycopg

from .postgres_schema import create_schema
from .transformer import TransformedPart


def connect(database_url: str):
    return psycopg.connect(database_url)


def validate_connection(database_url: str | None) -> bool:
    if not database_url:
        return False
    with connect(database_url) as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT 1")
            return cur.fetchone()[0] == 1


def ensure_schema(database_url: str) -> None:
    with connect(database_url) as conn:
        create_schema(conn)


def load_batch(database_url: str, parts: Sequence[TransformedPart]) -> dict[str, int]:
    counts = {"manufacturers": 0, "categories": 0, "parts": 0, "offers": 0}
    with connect(database_url) as conn:
        create_schema(conn)
        with conn.transaction():
            with conn.cursor() as cur:
                for item in parts:
                    cur.execute(
                        """
                        INSERT INTO manufacturers (name, normalized_name)
                        VALUES (%s, %s)
                        ON CONFLICT (normalized_name)
                        DO UPDATE SET updated_at = now()
                        RETURNING id
                        """,
                        (item.manufacturer.display_name, item.manufacturer.normalized_name),
                    )
                    manufacturer_id = cur.fetchone()[0]
                    counts["manufacturers"] += 1

                    parent_id = None
                    category_id = None
                    for path in item.category.ancestors(item.category.path) if hasattr(item.category, "ancestors") else []:
                        pass
                    pieces = item.category.path.split("/")
                    path_accum: list[str] = []
                    for piece in pieces:
                        path_accum.append(piece)
                        path = "/".join(path_accum)
                        cur.execute(
                            """
                            INSERT INTO categories (name, normalized_name, parent_id, path, source_category_id)
                            VALUES (%s, %s, %s, %s, %s)
                            ON CONFLICT (parent_id, normalized_name)
                            DO UPDATE SET updated_at = now()
                            RETURNING id
                            """,
                            (piece, piece.casefold(), parent_id, path, item.category.source_category_id),
                        )
                        parent_id = cur.fetchone()[0]
                        category_id = parent_id
                    counts["categories"] += 1

                    cur.execute(
                        """
                        INSERT INTO parts (
                          mpn, normalized_mpn, manufacturer_id, category_id, description,
                          package, datasheet_url, attributes, source, source_part_id
                        )
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s::jsonb, 'jlcpcb_parts_database', %s)
                        ON CONFLICT (manufacturer_id, mpn)
                        DO UPDATE SET
                          category_id = EXCLUDED.category_id,
                          description = EXCLUDED.description,
                          package = EXCLUDED.package,
                          datasheet_url = EXCLUDED.datasheet_url,
                          attributes = EXCLUDED.attributes,
                          source_part_id = EXCLUDED.source_part_id,
                          updated_at = now()
                        RETURNING id
                        """,
                        (
                            item.mpn,
                            item.normalized_mpn,
                            manufacturer_id,
                            category_id,
                            item.description,
                            item.package,
                            item.datasheet_url,
                            json.dumps(item.attributes),
                            item.source_part_id,
                        ),
                    )
                    part_id = cur.fetchone()[0]
                    counts["parts"] += 1

                    if item.offer:
                        cur.execute(
                            """
                            INSERT INTO part_offers (part_id, distributor, sku, price_breaks, stock, currency, fetched_at)
                            VALUES (%s, %s, %s, %s::jsonb, %s, %s, %s)
                            ON CONFLICT (distributor, sku)
                            DO UPDATE SET
                              part_id = EXCLUDED.part_id,
                              price_breaks = EXCLUDED.price_breaks,
                              stock = EXCLUDED.stock,
                              currency = EXCLUDED.currency,
                              fetched_at = EXCLUDED.fetched_at,
                              updated_at = now()
                            """,
                            (
                                part_id,
                                item.offer["distributor"],
                                item.offer["sku"],
                                json.dumps(item.offer["price_breaks"]),
                                item.offer["stock"],
                                item.offer["currency"],
                                item.offer.get("fetched_at") or datetime.now(timezone.utc),
                            ),
                        )
                        counts["offers"] += 1
    return counts
