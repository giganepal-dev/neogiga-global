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
    after_source_id: str | int | None = None,
) -> Iterator[dict[str, object]]:
    if offset and after_source_id is not None:
        raise ValueError("offset and after_source_id are mutually exclusive")
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
        if mapping.source_id not in parts_columns:
            raise RuntimeError(f"Stable source ID column is missing: {mapping.source_id}")
        source_id_sql = f'c."{mapping.source_id}"'
        where_sql = f" WHERE {source_id_sql} > ?" if after_source_id is not None else ""
        order_sql = f" ORDER BY {source_id_sql}"
        sql = f'SELECT {select_sql} FROM "{mapping.parts_table}" c{joins}{where_sql}{order_sql}'
        params: list[str | int] = []
        if after_source_id is not None:
            params.append(after_source_id)
        if limit is not None:
            sql += " LIMIT ?"
            params.append(limit)
        if offset:
            if limit is None:
                sql += " LIMIT -1"
            sql += " OFFSET ?"
            params.append(offset)
        for row in conn.execute(sql, params):
            yield dict(row)
    finally:
        conn.close()
