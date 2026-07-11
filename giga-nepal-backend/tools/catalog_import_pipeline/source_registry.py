from __future__ import annotations

from pathlib import Path
from typing import Any

import yaml

from .models import SourceManifest


class SourceRegistryError(RuntimeError):
    pass


def load_source_manifests(path: Path | str) -> dict[str, SourceManifest]:
    registry_path = Path(path)
    data = yaml.safe_load(registry_path.read_text(encoding="utf-8")) or {}
    manifests: dict[str, SourceManifest] = {}
    for code, payload in (data.get("sources") or {}).items():
        field_map = payload.get("field_map") or {}
        manifests[code] = SourceManifest(
            code=code,
            name=str(payload.get("name") or code),
            source_url=str(payload.get("source_url") or ""),
            license_name=str(payload.get("license_name") or ""),
            license_url=payload.get("license_url"),
            license_note=str(payload.get("license_note") or ""),
            redistribution_allowed=bool(payload.get("redistribution_allowed", False)),
            image_redistribution_allowed=bool(payload.get("image_redistribution_allowed", False)),
            official_source=bool(payload.get("official_source", False)),
            feed_path=payload.get("feed_path"),
            feed_format=str(payload.get("feed_format") or "csv").casefold(),
            source_page_url=payload.get("source_page_url"),
            data_year=int(payload.get("data_year") or 0),
            downloaded_at=payload.get("downloaded_at"),
            field_map={str(k): str(v) for k, v in field_map.items()},
            default_category=str(payload.get("default_category") or "Uncategorized/Needs Review"),
            notes=payload.get("notes"),
        )
    return manifests


def manifest_to_safe_report(manifest: SourceManifest) -> dict[str, Any]:
    return {
        "code": manifest.code,
        "name": manifest.name,
        "source_url": manifest.source_url,
        "official_source": manifest.official_source,
        "license_name": manifest.license_name,
        "license_url": manifest.license_url,
        "redistribution_allowed": manifest.redistribution_allowed,
        "image_redistribution_allowed": manifest.image_redistribution_allowed,
        "feed_configured": bool(manifest.feed_path),
        "feed_format": manifest.feed_format,
        "default_category": manifest.default_category,
        "notes": manifest.notes,
    }

