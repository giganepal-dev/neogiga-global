from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any

from .models import CatalogProductCandidate, REQUIRED_SOURCE_FIELDS, SourceManifest
from .normalization import canonical_identity, normalize_mpn


@dataclass
class ValidationReport:
    source_code: str
    rows_read: int = 0
    valid_records: int = 0
    invalid_records: int = 0
    duplicates: int = 0
    duplicate_rate: float = 0.0
    images_allowed: int = 0
    images_skipped: int = 0
    errors: list[dict[str, Any]] = field(default_factory=list)
    warnings: list[dict[str, Any]] = field(default_factory=list)
    stopped: bool = False
    stop_reason: str | None = None

    def to_dict(self) -> dict[str, Any]:
        return self.__dict__


class CatalogValidationError(RuntimeError):
    def __init__(self, report: ValidationReport) -> None:
        super().__init__(report.stop_reason or "catalog validation failed")
        self.report = report


def quality_score(product: CatalogProductCandidate) -> float:
    score = 1.0
    if not product.datasheet_url:
        score -= 0.10
    if not product.technical_specs:
        score -= 0.15
    if not product.parametric_attributes:
        score -= 0.10
    if not product.category or "Needs Review" in product.category:
        score -= 0.20
    if not product.source_url:
        score -= 0.15
    if not product.source_license:
        score -= 0.20
    return max(0.05, round(score, 2))


def validate_manifest(manifest: SourceManifest) -> None:
    if not manifest.official_source:
        raise ValueError(f"{manifest.code}: source is not marked official")
    if not manifest.redistribution_allowed:
        raise ValueError(f"{manifest.code}: redistribution is not permitted")
    if not manifest.license_name or manifest.license_name.casefold() in {"unknown", "n/a", "none"}:
        raise ValueError(f"{manifest.code}: license is unknown")
    if not manifest.source_url:
        raise ValueError(f"{manifest.code}: source_url is required")
    if not manifest.feed_path:
        raise ValueError(f"{manifest.code}: feed_path is required")


def validate_products(
    manifest: SourceManifest,
    products: list[CatalogProductCandidate],
    *,
    duplicate_threshold: float = 0.05,
    stop_on_image_rights_unknown: bool = True,
) -> ValidationReport:
    report = ValidationReport(source_code=manifest.code, rows_read=len(products))
    try:
        validate_manifest(manifest)
    except ValueError as exc:
        report.stopped = True
        report.stop_reason = str(exc)
        report.errors.append({"scope": "source", "reason": str(exc)})
        raise CatalogValidationError(report)

    seen: set[str] = set()
    valid: list[CatalogProductCandidate] = []
    for product in products:
        errors: list[str] = []
        if not product.manufacturer:
            errors.append("required manufacturer missing")
        if not product.mpn:
            errors.append("required MPN missing")
        if product.mpn and not normalize_mpn(product.mpn):
            errors.append("normalized MPN empty")
        if not product.category or "Needs Review" in product.category:
            errors.append("category mapping failed")
        if not product.source_license or product.source_license.casefold() in {"unknown", "n/a", "none"}:
            errors.append("license unknown")
        missing_provenance = sorted(REQUIRED_SOURCE_FIELDS - set(product.provenance.keys()))
        if missing_provenance:
            errors.append("missing provenance fields: " + ", ".join(missing_provenance))
        for image in product.images:
            if image.original_url and not image.redistribution_allowed:
                report.images_skipped += 1
                if stop_on_image_rights_unknown:
                    errors.append("image rights unknown or not redistributable")
            elif image.local_path and image.redistribution_allowed:
                report.images_allowed += 1
        identity = canonical_identity(product.manufacturer, product.mpn)
        if identity in seen:
            report.duplicates += 1
            errors.append("duplicate manufacturer+MPN within feed")
        seen.add(identity)
        product.quality_score = quality_score(product)
        if errors:
            report.invalid_records += 1
            report.errors.append({"source_part_id": product.source_part_id, "mpn": product.mpn, "errors": errors})
        else:
            valid.append(product)

    report.valid_records = len(valid)
    if report.rows_read:
        report.duplicate_rate = round(report.duplicates / report.rows_read, 4)
    if report.duplicate_rate > duplicate_threshold:
        report.stopped = True
        report.stop_reason = f"duplicate rate {report.duplicate_rate:.2%} exceeds threshold {duplicate_threshold:.2%}"
        raise CatalogValidationError(report)
    if report.invalid_records:
        report.stopped = True
        report.stop_reason = f"{report.invalid_records} record(s) failed validation"
        raise CatalogValidationError(report)
    return report

