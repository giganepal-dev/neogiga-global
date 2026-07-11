from __future__ import annotations

import csv
from pathlib import Path

import pytest
import yaml

from tools.catalog_import_pipeline.cli import main
from tools.catalog_import_pipeline.feed_reader import read_feed
from tools.catalog_import_pipeline.marketplaces import localized_overlays
from tools.catalog_import_pipeline.source_registry import load_source_manifests
from tools.catalog_import_pipeline.validator import CatalogValidationError, validate_products


def write_registry(tmp_path: Path, feed_path: Path, *, redistribution: bool = True, license_name: str = "Authorized Feed License") -> Path:
    registry = {
        "sources": {
            "licensed_test": {
                "name": "Licensed Test Feed",
                "source_url": "https://vendor.example/feed",
                "source_page_url": "https://vendor.example/products",
                "license_name": license_name,
                "license_url": "https://vendor.example/license",
                "license_note": "Authorized redistributable feed for tests.",
                "official_source": True,
                "redistribution_allowed": redistribution,
                "image_redistribution_allowed": False,
                "feed_path": str(feed_path),
                "feed_format": "csv",
                "data_year": 2026,
                "downloaded_at": "2026-07-11T00:00:00Z",
                "default_category": "Development Boards/SBCs/Test",
                "field_map": {
                    "source_part_id": "source_part_id",
                    "manufacturer": "manufacturer",
                    "mpn": "mpn",
                    "category": "category",
                    "name": "name",
                    "short_description": "short_description",
                    "technical_specs": "technical_specs",
                    "parametric_attributes": "parametric_attributes",
                    "datasheet_url": "datasheet_url",
                    "source_url": "source_url",
                },
            }
        }
    }
    path = tmp_path / "registry.yaml"
    path.write_text(yaml.safe_dump(registry), encoding="utf-8")
    return path


def write_feed(tmp_path: Path, rows: list[dict[str, str]]) -> Path:
    path = tmp_path / "feed.csv"
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=rows[0].keys())
        writer.writeheader()
        writer.writerows(rows)
    return path


def valid_rows() -> list[dict[str, str]]:
    return [
        {
            "source_part_id": "A1",
            "manufacturer": "Test Manufacturer",
            "mpn": "TM-001",
            "category": "Development Boards/SBCs/Test",
            "name": "Test Manufacturer TM-001",
            "short_description": "Authorized test product.",
            "technical_specs": '{"cpu":"test"}',
            "parametric_attributes": '{"wireless":"yes"}',
            "datasheet_url": "https://vendor.example/datasheet.pdf",
            "source_url": "https://vendor.example/products/tm-001",
        }
    ]


def test_reads_and_validates_licensed_feed(tmp_path: Path) -> None:
    feed = write_feed(tmp_path, valid_rows())
    registry = load_source_manifests(write_registry(tmp_path, feed))
    products = read_feed(registry["licensed_test"])
    report = validate_products(registry["licensed_test"], products)

    assert report.valid_records == 1
    assert products[0].manufacturer == "Test Manufacturer"
    assert products[0].quality_score >= 0.9


def test_blocks_unlicensed_feed(tmp_path: Path) -> None:
    feed = write_feed(tmp_path, valid_rows())
    registry = load_source_manifests(write_registry(tmp_path, feed, redistribution=False))

    with pytest.raises(CatalogValidationError) as exc:
        validate_products(registry["licensed_test"], read_feed(registry["licensed_test"]))

    assert "redistribution is not permitted" in exc.value.report.stop_reason


def test_blocks_missing_mpn(tmp_path: Path) -> None:
    rows = valid_rows()
    rows[0]["mpn"] = ""
    feed = write_feed(tmp_path, rows)
    registry = load_source_manifests(write_registry(tmp_path, feed))

    with pytest.raises(CatalogValidationError) as exc:
        validate_products(registry["licensed_test"], read_feed(registry["licensed_test"]))

    assert "required MPN missing" in str(exc.value.report.errors)


def test_localization_has_requested_market_fields() -> None:
    overlays = localized_overlays(
        "Test Manufacturer TM-001",
        "TM-001",
        "Development Boards",
        {"india": {"country": "India", "locale": "en-IN", "currency": "INR", "domain": "neogiga.in", "brand": "NeoGiga India"}},
    )

    assert overlays["india"]["currency"] == "INR"
    assert overlays["india"]["hreflang"] == "en-IN"
    assert overlays["india"]["canonical"].startswith("https://neogiga.in/products/")


def test_cli_blocks_when_no_feed_configured(tmp_path: Path) -> None:
    registry = tmp_path / "empty.yaml"
    registry.write_text("sources:\n  official:\n    name: Official\n    source_url: https://example.com\n    license_name: unknown\n    license_note: missing\n    official_source: true\n    redistribution_allowed: false\n    image_redistribution_allowed: false\n    feed_path:\n    feed_format: csv\n    data_year: 2026\n    default_category: Uncategorized/Needs Review\n    field_map: {}\n", encoding="utf-8")
    output = tmp_path / "output"

    assert main(["--registry", str(registry), "--output-dir", str(output)]) == 2
    assert (output / "PRODUCTION_CATALOG_IMPORT_REPORT.md").exists()
