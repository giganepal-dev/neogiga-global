"""Transform source SQLite records into NeoGiga catalog records."""

from __future__ import annotations

import json
import re
from dataclasses import dataclass, field
from datetime import datetime, timezone
from decimal import Decimal
from typing import Any

from .category_mapper import CategoryMapper, CategoryMapping
from .manufacturer_normalizer import ManufacturerName, normalize_manufacturer_name, normalize_mpn
from .schema_inspector import SourceMapping
from .unit_normalizer import normalize_attribute_value, normalize_unit_value


@dataclass
class TransformedPart:
    source_part_id: str
    mpn: str
    normalized_mpn: str
    manufacturer: ManufacturerName
    category: CategoryMapping
    description: str | None
    package: str | None
    datasheet_url: str | None
    attributes: dict[str, Any]
    offer: dict[str, Any] | None
    warnings: list[str] = field(default_factory=list)


def _value(record: dict[str, Any], column: str | None) -> Any:
    if not column:
        return None
    return record.get(column)


def _manufacturer_value(record: dict[str, Any], mapping: SourceMapping) -> Any:
    if mapping.manufacturer == "manufacturer_id" and record.get("__manufacturer_name"):
        return record.get("__manufacturer_name")
    return _value(record, mapping.manufacturer)


def _category_value(record: dict[str, Any], mapping: SourceMapping) -> Any:
    if mapping.category == "category_id":
        category_name = record.get("__category_name")
        category_parent = record.get("__category_parent")
        if category_parent and category_name:
            return f"{category_parent}/{category_name}"
        if category_name:
            return category_name
        if category_parent:
            return category_parent
    return _value(record, mapping.category)


def _text(value: Any) -> str | None:
    if value is None:
        return None
    text = str(value).strip()
    return text or None


def parse_price_breaks(raw: Any) -> list[dict[str, Any]]:
    if raw in (None, ""):
        return []
    if isinstance(raw, list):
        return raw
    text = str(raw).strip()
    try:
        parsed = json.loads(text)
        if isinstance(parsed, list):
            return parsed
        if isinstance(parsed, dict):
            return [{"quantity": key, "price": value} for key, value in parsed.items()]
    except json.JSONDecodeError:
        pass
    pairs: list[dict[str, Any]] = []
    for chunk in re.split(r"[;,]", text):
        if not chunk.strip():
            continue
        match = re.search(r"(?P<qty>\d+)\s*[:@]\s*(?P<price>[0-9.]+)", chunk)
        if not match:
            raise ValueError(f"malformed price break: {chunk}")
        pairs.append({"quantity": int(match.group("qty")), "price": match.group("price")})
    return pairs


def normalize_attributes(record: dict[str, Any], consumed_columns: set[str]) -> tuple[dict[str, Any], int, int]:
    attributes: dict[str, Any] = {"raw": {}}
    success = 0
    failure = 0
    for key, value in record.items():
        if key in consumed_columns or value in (None, ""):
            continue
        raw_text = str(value)
        parsed = normalize_unit_value(raw_text)
        normalized_key = re.sub(r"[^a-z0-9]+", "_", key.casefold()).strip("_") or key
        if parsed.ok:
            attributes[normalized_key] = normalize_attribute_value(key, raw_text)
            success += 1
        else:
            attributes["raw"][key] = value
            failure += 1
    if not attributes["raw"]:
        attributes.pop("raw")
    attributes["source_metadata"] = {
        "source_name": "CDFER jlcpcb-parts-database",
        "source_url": "https://github.com/CDFER/jlcpcb-parts-database",
        "source_file": "jlcpcb-components.sqlite3",
        "source_page_url": "https://cdfer.github.io/jlcpcb-parts-database/",
        "downloaded_at": None,
        "imported_at": datetime.now(timezone.utc).isoformat(),
        "data_year": datetime.now(timezone.utc).year,
        "license_note": "Repository is MIT licensed; upstream component data originates from open JLCPCB/LCSC dataset exports.",
        "confidence_level": "source-provided",
    }
    return attributes, success, failure


def transform_record(
    record: dict[str, Any],
    mapping: SourceMapping,
    category_mapper: CategoryMapper | None = None,
) -> TransformedPart:
    category_mapper = category_mapper or CategoryMapper()
    source_part_id = _text(_value(record, mapping.source_id))
    manufacturer_raw = _text(_manufacturer_value(record, mapping))
    mpn = _text(_value(record, mapping.mpn))
    warnings: list[str] = []
    if not source_part_id:
        raise ValueError("missing stable source part ID")
    if not manufacturer_raw:
        raise ValueError("missing manufacturer")
    if not mpn:
        mpn = f"LCSC-{source_part_id}"
        warnings.append("missing MPN; using stable LCSC source ID as fallback")

    consumed = {
        column
        for column in (
            mapping.source_id,
            mapping.mpn,
            mapping.manufacturer,
            mapping.category,
            mapping.description,
            mapping.package,
            mapping.datasheet_url,
            mapping.sku,
            mapping.stock,
            mapping.price_breaks,
        )
        if column
    }
    attributes, _, _ = normalize_attributes(record, consumed)
    category = category_mapper.map_category(_text(_category_value(record, mapping)), _text(_value(record, mapping.category)))
    if category.is_unknown:
        warnings.append("unknown category mapped to Uncategorized/Needs Review")

    offer = None
    sku = _text(_value(record, mapping.sku))
    if sku:
        stock_raw = _value(record, mapping.stock)
        try:
            stock = int(Decimal(str(stock_raw))) if stock_raw not in (None, "") else None
        except Exception:
            stock = None
            warnings.append("malformed stock value")
        try:
            price_breaks = parse_price_breaks(_value(record, mapping.price_breaks))
        except ValueError as exc:
            price_breaks = []
            warnings.append(str(exc))
        offer = {
            "distributor": "LCSC/JLCPCB",
            "sku": sku,
            "price_breaks": price_breaks,
            "stock": stock,
            "currency": "USD",
            "fetched_at": datetime.now(timezone.utc).isoformat(),
        }

    return TransformedPart(
        source_part_id=source_part_id,
        mpn=mpn,
        normalized_mpn=normalize_mpn(mpn),
        manufacturer=normalize_manufacturer_name(manufacturer_raw),
        category=category,
        description=_text(_value(record, mapping.description)),
        package=_text(_value(record, mapping.package)),
        datasheet_url=_text(_value(record, mapping.datasheet_url)),
        attributes=attributes,
        offer=offer,
        warnings=warnings,
    )
