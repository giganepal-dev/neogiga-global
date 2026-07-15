import json
import sqlite3
from pathlib import Path
from types import SimpleNamespace

import pytest

from tools.jlcpcb_etl import image_candidates


FRONT_URL = "https://assets.lcsc.com/images/lcsc/900x900/20260715_Demo_C1002_front.jpg"
BACK_URL = "https://assets.lcsc.com/images/lcsc/900x900/20260715_Demo_C1002_back.jpg"


def _extra(images, url="https://lcsc.com/product-detail/Demo_C1002.html"):
    return json.dumps({"images": images, "url": url})


def _sqlite_source(path: Path, extra: str) -> None:
    conn = sqlite3.connect(path)
    conn.execute("CREATE TABLE components (lcsc INTEGER PRIMARY KEY, extra TEXT)")
    conn.execute("INSERT INTO components (lcsc, extra) VALUES (1002, ?)", (extra,))
    conn.commit()
    conn.close()


def test_exact_https_host_and_900_path_are_required():
    assert image_candidates.normalize_candidate_url(FRONT_URL) == FRONT_URL
    assert image_candidates.normalize_candidate_url(FRONT_URL.replace("https://", "http://")) is None
    assert image_candidates.normalize_candidate_url(FRONT_URL.replace("assets.lcsc.com", "assets.lcsc.com.evil.test")) is None
    assert image_candidates.normalize_candidate_url(FRONT_URL.replace("assets.lcsc.com", "assets.lcsc.com@evil.test")) is None
    assert image_candidates.normalize_candidate_url(FRONT_URL.replace("/900x900/", "/224x224/")) is None
    assert image_candidates.normalize_candidate_url(FRONT_URL + "?tracking=1") is None


def test_front_900px_image_is_preferred_over_an_earlier_back_image():
    selected = image_candidates.select_preferred_candidate(
        "1002",
        _extra(
            [
                {"900x900": BACK_URL},
                {"224x224": FRONT_URL.replace("900x900", "224x224"), "900x900": FRONT_URL},
            ]
        ),
    )

    assert selected.reason == "selected_front"
    assert selected.candidate is not None
    assert selected.candidate.candidate_url == FRONT_URL
    assert selected.candidate.image_role == "front"
    assert selected.candidate.image_index == 1


def test_invalid_or_non_allowlisted_metadata_is_not_staged():
    assert image_candidates.select_preferred_candidate("1002", "not-json").reason == "invalid_extra"
    assert image_candidates.select_preferred_candidate("1002", _extra([])).reason == "no_images"
    assert image_candidates.select_preferred_candidate("1002", _extra([{"224x224": "https://example.test/x.jpg"}])).reason == "no_900_image"
    assert image_candidates.select_preferred_candidate(
        "1002", _extra([{"900x900": "https://example.test/900x900/front.jpg"}])
    ).reason == "disallowed_url"


def test_source_reader_is_read_only_and_honors_limit_and_offset(tmp_path):
    source = tmp_path / "parts.sqlite3"
    conn = sqlite3.connect(source)
    conn.execute("CREATE TABLE components (lcsc INTEGER PRIMARY KEY, extra TEXT)")
    conn.executemany(
        "INSERT INTO components (lcsc, extra) VALUES (?, ?)",
        [(1002, _extra([{"900x900": FRONT_URL}])), (1003, _extra([{"900x900": BACK_URL}]))],
    )
    conn.commit()
    conn.close()

    rows = list(image_candidates.stream_component_rows(source, limit=1, offset=1))

    assert rows == [("1003", _extra([{"900x900": BACK_URL}]))]


def test_default_run_is_dry_run_and_performs_no_candidate_write(tmp_path, monkeypatch, capsys):
    source = tmp_path / "parts.sqlite3"
    _sqlite_source(source, _extra([{"900x900": FRONT_URL}]))

    class Result:
        def fetchall(self):
            return [{"source_part_id": "1002", "product_id": 42}]

    class ReadOnlyConnection:
        def __enter__(self):
            return self

        def __exit__(self, exc_type, exc, traceback):
            return False

        def execute(self, sql, params=None):
            assert "catalog_product_sources" in sql
            assert "INSERT" not in sql.upper()
            return Result()

        def cursor(self):
            raise AssertionError("dry-run must not open a write cursor")

    monkeypatch.setattr(
        image_candidates,
        "resolve_connection",
        lambda **_kwargs: SimpleNamespace(dsn="postgresql://redacted.invalid/neogiga"),
    )
    monkeypatch.setattr(image_candidates, "_connect_database", lambda _dsn: ReadOnlyConnection())

    assert image_candidates.run(["--sqlite-file", str(source)]) == 0
    report = json.loads(capsys.readouterr().out)

    assert report["mode"] == "dry_run"
    assert report["upserts_planned"] == 1
    assert report["upserts_applied"] == 0
    assert report["asset_downloads"] == 0
    assert report["public_activations"] == 0
    assert report["rights_status"] == "pending_review"
    assert report["is_active"] is False


def test_apply_requires_a_second_explicit_confirmation(tmp_path):
    source = tmp_path / "parts.sqlite3"
    _sqlite_source(source, _extra([{"900x900": FRONT_URL}]))

    with pytest.raises(RuntimeError, match="--apply --yes"):
        image_candidates.run(["--sqlite-file", str(source), "--apply"])


def test_candidate_insert_is_hidden_and_never_overwrites_an_existing_review():
    sql = image_candidates.UPSERT_SQL

    assert "ON CONFLICT (product_id, candidate_url)" in sql
    assert "DO NOTHING" in sql
    assert "DO UPDATE" not in sql
    assert "true, false, 70.00" in sql
    assert "'pending_review'" in sql
    assert "'not_requested'" in sql
    assert "product_images" not in sql


def test_additive_migration_has_provenance_rights_and_hidden_fields():
    backend = Path(__file__).resolve().parents[3]
    migration = (
        backend
        / "database/migrations/2026_07_15_000300_extend_product_image_candidates_for_jlcpcb_staging.php"
    ).read_text(encoding="utf-8")

    for field in (
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
        "rights_status",
        "rights_basis",
        "rights_review_required",
        "is_active",
        "asset_fetch_status",
    ):
        assert field in migration
    assert "default(false)" in migration
    assert "Deliberately non-destructive" in migration
