from __future__ import annotations

import csv
import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Iterable

from .models import CatalogProductCandidate, ImageCandidate, SourceManifest
from .normalization import normalize_text, stable_sku


class FeedReadError(RuntimeError):
    pass


def read_feed(manifest: SourceManifest, *, limit: int = 0) -> list[CatalogProductCandidate]:
    if not manifest.feed_path:
        raise FeedReadError(f"Source {manifest.code} has no feed_path configured")
    path = Path(manifest.feed_path).expanduser()
    if not path.exists():
        raise FeedReadError(f"Feed file does not exist: {path}")
    rows = list(_read_rows(path, manifest.feed_format))
    if limit > 0:
        rows = rows[:limit]
    return [row_to_candidate(manifest, row, path.name) for row in rows]


def _read_rows(path: Path, feed_format: str) -> Iterable[dict[str, Any]]:
    if feed_format == "csv":
        with path.open("r", encoding="utf-8-sig", newline="") as handle:
            yield from csv.DictReader(handle)
        return
    if feed_format == "jsonl":
        with path.open("r", encoding="utf-8") as handle:
            for line in handle:
                if line.strip():
                    yield json.loads(line)
        return
    if feed_format == "json":
        payload = json.loads(path.read_text(encoding="utf-8"))
        if isinstance(payload, dict):
            payload = payload.get("products") or payload.get("items") or []
        for row in payload:
            yield dict(row)
        return
    raise FeedReadError(f"Unsupported feed format: {feed_format}")


def _field(row: dict[str, Any], manifest: SourceManifest, key: str) -> str | None:
    source_key = manifest.field_map.get(key, key)
    value = row.get(source_key)
    text = normalize_text(value)
    return text or None


def row_to_candidate(manifest: SourceManifest, row: dict[str, Any], source_file: str) -> CatalogProductCandidate:
    manufacturer = _field(row, manifest, "manufacturer") or ""
    mpn = _field(row, manifest, "mpn") or ""
    category = _field(row, manifest, "category") or manifest.default_category
    subcategory = _field(row, manifest, "subcategory")
    name = _field(row, manifest, "name") or " ".join(piece for piece in [manufacturer, mpn] if piece).strip()
    sku = _field(row, manifest, "sku") or (stable_sku(manufacturer, mpn) if manufacturer and mpn else None)
    source_part_id = _field(row, manifest, "source_part_id") or sku or mpn
    now = datetime.now(timezone.utc).isoformat()
    specs = _json_field(row, manifest, "technical_specs")
    attrs = _json_field(row, manifest, "parametric_attributes")
    image = ImageCandidate(
        original_url=_field(row, manifest, "image_url"),
        local_path=_field(row, manifest, "image_local_path"),
        checksum=_field(row, manifest, "image_checksum"),
        width=_int_field(row, manifest, "image_width"),
        height=_int_field(row, manifest, "image_height"),
        alt_text=_field(row, manifest, "image_alt_text"),
        caption=_field(row, manifest, "image_caption"),
        copyright=_field(row, manifest, "image_copyright"),
        source=manifest.name,
        license=manifest.license_name,
        redistribution_allowed=manifest.image_redistribution_allowed,
    )
    images = [image] if image.original_url or image.local_path else []
    provenance = {
        "source_name": manifest.name,
        "source_url": manifest.source_url,
        "source_file": source_file,
        "source_page_url": manifest.source_page_url or manifest.source_url,
        "downloaded_at": manifest.downloaded_at,
        "imported_at": now,
        "data_year": manifest.data_year,
        "license_note": manifest.license_note,
        "confidence_level": "official_licensed_feed" if manifest.official_source else "unverified",
        "original_raw_value": row,
        "normalized_value": {
            "manufacturer": manufacturer,
            "mpn": mpn,
            "sku": sku,
            "category": category,
        },
    }
    return CatalogProductCandidate(
        source_part_id=source_part_id or "",
        manufacturer=manufacturer,
        mpn=mpn,
        brand=_field(row, manifest, "brand") or manufacturer,
        sku=sku,
        product_family=_field(row, manifest, "product_family"),
        category=category,
        subcategory=subcategory,
        name=name,
        short_description=_field(row, manifest, "short_description"),
        technical_specs=specs,
        parametric_attributes=attrs,
        datasheet_url=_field(row, manifest, "datasheet_url"),
        compliance=_field(row, manifest, "compliance"),
        lifecycle=_field(row, manifest, "lifecycle"),
        country_of_origin=_field(row, manifest, "country_of_origin"),
        source_url=_field(row, manifest, "source_url") or manifest.source_url,
        source_name=manifest.name,
        source_timestamp=manifest.downloaded_at or now,
        source_license=manifest.license_name,
        provenance=provenance,
        quality_score=0.0,
        images=images,
        documents=[],
        raw=row,
    )


def _json_field(row: dict[str, Any], manifest: SourceManifest, key: str) -> dict[str, Any]:
    text = _field(row, manifest, key)
    if not text:
        return {}
    try:
        payload = json.loads(text)
        return payload if isinstance(payload, dict) else {"value": payload}
    except json.JSONDecodeError:
        return {"raw": text}


def _int_field(row: dict[str, Any], manifest: SourceManifest, key: str) -> int | None:
    text = _field(row, manifest, key)
    if not text:
        return None
    try:
        return int(float(text))
    except ValueError:
        return None
