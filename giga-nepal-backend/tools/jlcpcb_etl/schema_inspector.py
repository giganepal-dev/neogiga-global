"""SQLite schema discovery and source field inference."""

from __future__ import annotations

import json
import sqlite3
from dataclasses import dataclass
from pathlib import Path
from typing import Any

import yaml


@dataclass(frozen=True)
class SourceMapping:
    parts_table: str
    source_id: str
    mpn: str | None
    manufacturer: str | None
    category: str | None
    description: str | None
    package: str | None
    datasheet_url: str | None
    sku: str | None
    stock: str | None
    price_breaks: str | None


def _column_score(name: str, candidates: tuple[str, ...]) -> int:
    lowered = name.lower()
    return max((10 if lowered == candidate else 5 if candidate in lowered else 0) for candidate in candidates)


def inspect_sqlite_schema(sqlite_path: Path, output_path: Path | None = None) -> dict[str, Any]:
    conn = sqlite3.connect(f"file:{sqlite_path}?mode=ro", uri=True)
    conn.row_factory = sqlite3.Row
    try:
        tables = [row["name"] for row in conn.execute("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")]
        report: dict[str, Any] = {"sqlite_path": str(sqlite_path), "tables": {}, "detected_mapping": None}
        for table in tables:
            columns = [dict(row) for row in conn.execute(f'PRAGMA table_info("{table}")')]
            indexes = [dict(row) for row in conn.execute(f'PRAGMA index_list("{table}")')]
            count = conn.execute(f'SELECT COUNT(*) AS c FROM "{table}"').fetchone()["c"]
            report["tables"][table] = {"columns": columns, "indexes": indexes, "row_count": count}
        mapping = load_configured_mapping(report) or infer_source_mapping(report)
        report["detected_mapping"] = mapping.__dict__ if mapping else None
        if output_path:
            output_path.parent.mkdir(parents=True, exist_ok=True)
            output_path.write_text(json.dumps(report, indent=2, default=str), encoding="utf-8")
        return report
    finally:
        conn.close()


def infer_source_mapping(report: dict[str, Any]) -> SourceMapping | None:
    best: tuple[int, str, list[str]] | None = None
    for table, table_report in report.get("tables", {}).items():
        columns = [column["name"] for column in table_report.get("columns", [])]
        score = 0
        for column in columns:
            score += _column_score(column, ("mpn", "partnumber", "part_number", "component", "part"))
            score += _column_score(column, ("manufacturer", "mfr", "brand"))
            score += _column_score(column, ("lcsc", "sku", "code"))
        if best is None or score > best[0]:
            best = (score, table, columns)
    if not best or best[0] <= 0:
        return None
    _, table, columns = best

    def pick(*names: str) -> str | None:
        scored = sorted(((_column_score(column, names), column) for column in columns), reverse=True)
        return scored[0][1] if scored and scored[0][0] > 0 else None

    source_id = pick("id", "component_id", "lcsc", "sku", "code") or columns[0]
    return SourceMapping(
        parts_table=table,
        source_id=source_id,
        mpn=pick("mpn", "partnumber", "part_number", "component_part_number", "manufacturer_part_number"),
        manufacturer=pick("manufacturer", "mfr", "brand"),
        category=pick("category", "subcategory", "firstcategory", "secondcategory"),
        description=pick("description", "desc", "name"),
        package=pick("package", "footprint", "case"),
        datasheet_url=pick("datasheet", "pdf"),
        sku=pick("lcsc", "sku", "code"),
        stock=pick("stock", "inventory", "quantity"),
        price_breaks=pick("price", "prices", "price_breaks"),
    )


def load_configured_mapping(report: dict[str, Any], mapping_file: Path | None = None) -> SourceMapping | None:
    mapping_file = mapping_file or Path(__file__).with_name("mappings") / "source_fields.yaml"
    if not mapping_file.exists():
        return None
    data = yaml.safe_load(mapping_file.read_text(encoding="utf-8")) or {}
    tables = data.get("tables", {})
    for table, fields in tables.items():
        table_report = report.get("tables", {}).get(table)
        if not table_report:
            continue
        columns = {column["name"] for column in table_report.get("columns", [])}
        configured_values = [value for value in fields.values() if value]
        if all(value in columns for value in configured_values):
            return SourceMapping(
                parts_table=table,
                source_id=fields["source_id"],
                mpn=fields.get("mpn"),
                manufacturer=fields.get("manufacturer"),
                category=fields.get("category"),
                description=fields.get("description"),
                package=fields.get("package"),
                datasheet_url=fields.get("datasheet_url"),
                sku=fields.get("sku"),
                stock=fields.get("stock"),
                price_breaks=fields.get("price_breaks"),
            )
    return None
