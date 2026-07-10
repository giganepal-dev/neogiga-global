"""Read source parts from the discovered SQLite table."""

from __future__ import annotations

import sqlite3
from collections.abc import Iterator
from pathlib import Path

from .schema_inspector import SourceMapping


def _table_columns(conn: sqlite3.Connection, table: str) -> set[str]:
    return {row["name"] for row in conn.execute(f'PRAGMA table_info("{table}")')}


def stream_source_rows(
    sqlite_path: Path,
    mapping: SourceMapping,
    limit: int | None = None,
    offset: int = 0,
) -> Iterator[dict[str, object]]:
    conn = sqlite3.connect(f"file:{sqlite_path}?mode=ro", uri=True)
    conn.row_factory = sqlite3.Row
    try:
        tables = {
            row["name"]
            for row in conn.execute("SELECT name FROM sqlite_master WHERE type IN ('table', 'view')")
        }
        parts_columns = _table_columns(conn, mapping.parts_table)
        select_sql = "c.*"
        joins = ""
        if mapping.category == "category_id" and "categories" in tables and "category_id" in parts_columns:
            category_columns = _table_columns(conn, "categories")
            if {"id", "category", "subcategory"}.issubset(category_columns):
                select_sql += ', cat.category AS __category_parent, cat.subcategory AS __category_name'
                joins += ' LEFT JOIN "categories" cat ON c."category_id" = cat."id"'
        if mapping.manufacturer == "manufacturer_id" and "manufacturers" in tables and "manufacturer_id" in parts_columns:
            manufacturer_columns = _table_columns(conn, "manufacturers")
            if {"id", "name"}.issubset(manufacturer_columns):
                select_sql += ', mf.name AS __manufacturer_name'
                joins += ' LEFT JOIN "manufacturers" mf ON c."manufacturer_id" = mf."id"'
        order_sql = f' ORDER BY c."{mapping.source_id}"' if mapping.source_id in parts_columns else ""
        sql = f'SELECT {select_sql} FROM "{mapping.parts_table}" c{joins}{order_sql}'
        params: list[int] = []
        if limit is not None:
            sql += " LIMIT ?"
            params.append(limit)
        if offset:
            sql += " OFFSET ?"
            params.append(offset)
        for row in conn.execute(sql, params):
            yield dict(row)
    finally:
        conn.close()
