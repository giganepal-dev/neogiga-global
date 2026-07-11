from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime, timezone
from typing import Any


REQUIRED_SOURCE_FIELDS = {
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
}


@dataclass(frozen=True)
class SourceManifest:
    code: str
    name: str
    source_url: str
    license_name: str
    license_url: str | None
    license_note: str
    redistribution_allowed: bool
    image_redistribution_allowed: bool
    official_source: bool
    feed_path: str | None
    feed_format: str
    source_page_url: str | None
    data_year: int
    downloaded_at: str | None
    field_map: dict[str, str]
    default_category: str
    notes: str | None = None

    @property
    def can_import_records(self) -> bool:
        return self.official_source and self.redistribution_allowed and bool(self.feed_path)

    @property
    def can_import_images(self) -> bool:
        return self.can_import_records and self.image_redistribution_allowed


@dataclass
class ImageCandidate:
    original_url: str | None = None
    local_path: str | None = None
    checksum: str | None = None
    width: int | None = None
    height: int | None = None
    alt_text: str | None = None
    caption: str | None = None
    copyright: str | None = None
    source: str | None = None
    license: str | None = None
    redistribution_allowed: bool = False


@dataclass
class CatalogProductCandidate:
    source_part_id: str
    manufacturer: str
    mpn: str
    brand: str | None
    sku: str | None
    product_family: str | None
    category: str
    subcategory: str | None
    name: str
    short_description: str | None
    technical_specs: dict[str, Any]
    parametric_attributes: dict[str, Any]
    datasheet_url: str | None
    compliance: str | None
    lifecycle: str | None
    country_of_origin: str | None
    source_url: str
    source_name: str
    source_timestamp: str
    source_license: str
    provenance: dict[str, Any]
    quality_score: float
    images: list[ImageCandidate] = field(default_factory=list)
    documents: list[dict[str, Any]] = field(default_factory=list)
    raw: dict[str, Any] = field(default_factory=dict)
    warnings: list[str] = field(default_factory=list)

    @property
    def imported_at(self) -> str:
        return datetime.now(timezone.utc).isoformat()

