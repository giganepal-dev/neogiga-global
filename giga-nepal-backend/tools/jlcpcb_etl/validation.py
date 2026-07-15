"""Validation report generation."""

from __future__ import annotations

import json
from collections import Counter
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from .transformer import TransformedPart


@dataclass
class ValidationAccumulator:
    """Streaming validation counters for imports too large to retain in memory."""

    total_rows_processed: int = 0
    total_offers: int = 0
    records_without_category: int = 0
    records_without_datasheet: int = 0
    records_without_package: int = 0
    category_counts: Counter[str] = field(default_factory=Counter)
    manufacturer_counts: Counter[str] = field(default_factory=Counter)

    def add(self, part: TransformedPart) -> None:
        self.total_rows_processed += 1
        self.total_offers += int(bool(part.offer))
        self.records_without_category += int(part.category.is_unknown)
        self.records_without_datasheet += int(not part.datasheet_url)
        self.records_without_package += int(not part.package)
        self.category_counts[part.category.path] += 1
        self.manufacturer_counts[part.manufacturer.normalized_name] += 1

    @classmethod
    def from_parts(cls, parts: list[TransformedPart]) -> "ValidationAccumulator":
        accumulator = cls()
        for part in parts:
            accumulator.add(part)
        return accumulator


def build_validation_report(
    *,
    source_url: str,
    source_checksum: str | None,
    schema_report: dict[str, Any] | None,
    processed: list[TransformedPart],
    skipped: Counter[str],
    started_at: str,
    accumulator: ValidationAccumulator | None = None,
) -> dict[str, Any]:
    metrics = accumulator or ValidationAccumulator.from_parts(processed)
    category_counts = metrics.category_counts
    manufacturer_counts = metrics.manufacturer_counts
    detected_mapping = (schema_report or {}).get("detected_mapping") or {}
    source_table = detected_mapping.get("parts_table")
    source_table_count = (
        ((schema_report or {}).get("tables", {}).get(source_table) or {}).get("row_count")
        if source_table
        else None
    )
    return {
        "source_repository": "https://github.com/CDFER/jlcpcb-parts-database",
        "source_download_url": source_url,
        "source_checksum": source_checksum,
        "source_schema_summary": {
            "tables": list((schema_report or {}).get("tables", {}).keys()),
            "detected_mapping": (schema_report or {}).get("detected_mapping"),
        },
        "started_at": started_at,
        "finished_at": datetime.now(timezone.utc).isoformat(),
        "total_source_rows": source_table_count
        if source_table_count is not None
        else sum(t.get("row_count", 0) for t in (schema_report or {}).get("tables", {}).values()),
        "total_rows_processed": metrics.total_rows_processed,
        "total_manufacturers_created_or_matched": len(manufacturer_counts),
        "total_categories_created_or_matched": len(category_counts),
        "total_parts_inserted": 0,
        "total_parts_updated": 0,
        "total_offers_inserted_or_updated": metrics.total_offers,
        "total_skipped": sum(skipped.values()),
        "skipped_grouped_by_reason": dict(skipped),
        "parts_per_category": dict(category_counts),
        "parts_per_manufacturer": dict(manufacturer_counts),
        "records_without_category": metrics.records_without_category,
        "records_without_datasheet": metrics.records_without_datasheet,
        "records_without_package": metrics.records_without_package,
        "attribute_normalization_success_count": 0,
        "attribute_normalization_failure_count": 0,
        "duplicate_mpn_conflicts": 0,
        "rows_per_second": None,
    }


def write_validation_report(report: dict[str, Any], output_dir: Path) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)
    (output_dir / "validation_report.json").write_text(json.dumps(report, indent=2, default=str), encoding="utf-8")
    lines = [
        "# JLCPCB ETL Validation Report",
        "",
        f"- Source: {report['source_repository']}",
        f"- Download URL: {report['source_download_url']}",
        f"- Checksum: {report['source_checksum']}",
        f"- Processed rows: {report['total_rows_processed']}",
        f"- Skipped rows: {report['total_skipped']}",
        f"- Unknown categories: {report['records_without_category']}",
        f"- Records without datasheet: {report['records_without_datasheet']}",
        f"- Records without package: {report['records_without_package']}",
        "",
        "Full machine-readable report is in `validation_report.json`.",
    ]
    (output_dir / "validation_report.md").write_text("\n".join(lines) + "\n", encoding="utf-8")
