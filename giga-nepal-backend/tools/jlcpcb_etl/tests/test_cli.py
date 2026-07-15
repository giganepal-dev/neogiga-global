import json
import sqlite3
from types import SimpleNamespace

import pytest

from tools.jlcpcb_etl import cli
from tools.jlcpcb_etl.canonical_adapter import AdapterResult
from tools.jlcpcb_etl.checkpoint import CheckpointStore
from tools.jlcpcb_etl.config import Settings


def _configure_test_settings(tmp_path, monkeypatch, *, batch_size=5000):
    output_dir = tmp_path / "output"
    checkpoint_dir = tmp_path / "checkpoints"
    monkeypatch.setattr(
        cli.Settings,
        "from_env",
        classmethod(
            lambda cls, batch_size=batch_size: Settings(
                database_url=None,
                sqlite_url_override=None,
                output_dir=output_dir,
                checkpoint_dir=checkpoint_dir,
                batch_size=batch_size,
            )
        ),
    )
    return output_dir, checkpoint_dir


def test_dry_run_does_not_write_catalog(tmp_path, monkeypatch):
    _configure_test_settings(tmp_path, monkeypatch)
    sqlite_path = tmp_path / "parts.sqlite3"
    conn = sqlite3.connect(sqlite_path)
    conn.execute(
        "CREATE TABLE components (id TEXT PRIMARY KEY, mpn TEXT, manufacturer TEXT, category TEXT, description TEXT, package TEXT, datasheet TEXT, lcsc TEXT, stock TEXT, prices TEXT)"
    )
    conn.execute(
        "INSERT INTO components VALUES ('C1', 'ABC-1', 'Texas Instruments', 'Ceramic Capacitors', 'Demo', '0603', '', 'C1', '1', '')"
    )
    conn.commit()
    conn.close()

    calls = {"ensure_schema": 0, "load_batch": 0}

    def fail_ensure_schema(_database_url):
        calls["ensure_schema"] += 1
        raise AssertionError("dry-run must not create schema")

    def fail_load_batch(_database_url, _parts):
        calls["load_batch"] += 1
        raise AssertionError("dry-run must not load rows")

    monkeypatch.setattr(cli, "ensure_schema", fail_ensure_schema)
    monkeypatch.setattr(cli, "load_batch", fail_load_batch)
    monkeypatch.setenv("DATABASE_URL", "postgresql://user:pass@example.invalid/db")
    monkeypatch.setattr(cli, "validate_connection", lambda _database_url: True)

    result = cli.run(["--dry-run", "--sqlite-file", str(sqlite_path), "--limit", "100", "--log-level", "ERROR"])
    assert result == 0
    assert calls == {"ensure_schema": 0, "load_batch": 0}


def test_scale_import_flag_is_explicit():
    args = cli.build_parser().parse_args(["--target", "neogiga", "--publish", "--pilot", "--scale-import", "--limit", "20000"])

    assert args.scale_import is True
    assert args.limit == 20000
    assert args.scale_import_max == 20000


def test_scale_import_max_can_be_raised_for_controlled_batches():
    args = cli.build_parser().parse_args([
        "--target",
        "neogiga",
        "--publish",
        "--pilot",
        "--scale-import",
        "--scale-import-max",
        "70000",
        "--limit",
        "70000",
    ])

    assert args.scale_import is True
    assert args.scale_import_max == 70000
    assert args.limit == 70000


def test_offset_is_explicit_and_zero_based():
    args = cli.build_parser().parse_args(["--offset", "70000", "--limit", "5000"])

    assert args.offset == 70000
    assert args.limit == 5000


def test_after_source_id_is_an_explicit_keyset_cursor():
    args = cli.build_parser().parse_args(["--after-source-id", "C70000", "--limit", "5000"])

    assert args.after_source_id == "C70000"
    assert args.offset is None


def test_dry_run_passes_offset_to_source_reader(tmp_path, monkeypatch):
    _configure_test_settings(tmp_path, monkeypatch)
    sqlite_path = tmp_path / "parts.sqlite3"
    conn = sqlite3.connect(sqlite_path)
    conn.execute(
        "CREATE TABLE components (id TEXT PRIMARY KEY, mpn TEXT, manufacturer TEXT, category TEXT, description TEXT, package TEXT, datasheet TEXT, lcsc TEXT, stock TEXT, prices TEXT)"
    )
    for index in range(1, 4):
        conn.execute(
            "INSERT INTO components VALUES (?, ?, 'Texas Instruments', 'Ceramic Capacitors', 'Demo', '0603', '', ?, '1', '')",
            (f'C{index}', f'ABC-{index}', f'C{index}'),
        )
    conn.commit()
    conn.close()

    monkeypatch.delenv("DATABASE_URL", raising=False)
    result = cli.run([
        "--dry-run",
        "--sqlite-file",
        str(sqlite_path),
        "--offset",
        "2",
        "--limit",
        "1",
        "--log-level",
        "ERROR",
    ])

    assert result == 0


def _parts_database(path, count):
    conn = sqlite3.connect(path)
    conn.execute(
        "CREATE TABLE components (id TEXT PRIMARY KEY, mpn TEXT, manufacturer TEXT, category TEXT, description TEXT, package TEXT, datasheet TEXT, lcsc TEXT, stock TEXT, prices TEXT)"
    )
    conn.executemany(
        "INSERT INTO components VALUES (?, ?, 'Texas Instruments', 'Ceramic Capacitors', 'Demo', '0603', '', ?, '1', '')",
        [(f"C{index:05d}", f"ABC-{index}", f"C{index:05d}") for index in range(1, count + 1)],
    )
    conn.commit()
    conn.close()


def _configure_fake_neogiga(tmp_path, monkeypatch, *, fail_on_call=None):
    output_dir, checkpoint_dir = _configure_test_settings(tmp_path, monkeypatch)
    monkeypatch.setattr(
        cli,
        "_resolve_neogiga_connection",
        lambda *_args, **_kwargs: SimpleNamespace(dsn="postgresql://not-used", redacted="postgresql://***", source="CLI"),
    )

    class FakeAdapter:
        def __init__(self):
            self.calls = []
            self.provenance = None

        def connection_check(self):
            return True

        def validate_resume_checkpoint(self, *_args):
            return None

        def publish(self, parts, **kwargs):
            items = list(parts)
            self.calls.append((items, kwargs))
            if fail_on_call is not None and len(self.calls) == fail_on_call:
                raise RuntimeError("simulated atomic chunk failure")
            errors = kwargs.get("transform_errors") or []
            return AdapterResult(
                import_batch_id=f"batch-{len(self.calls)}",
                after_source_id=kwargs.get("after_source_id"),
                last_source_id=kwargs.get("last_source_id"),
                rows_read=kwargs["source_rows_read"],
                rows_transformed=len(items),
                products_inserted=len(items),
                skipped=len(errors),
            )

    fake = FakeAdapter()

    def create_neogiga(_dsn, **kwargs):
        if not kwargs["dry_run"]:
            fake.provenance = kwargs.get("source_provenance")
        return fake

    monkeypatch.setattr(cli.TargetAdapterRegistry, "create_neogiga", staticmethod(create_neogiga))
    return fake, output_dir, checkpoint_dir


def test_neogiga_scale_import_uses_bounded_atomic_keyset_chunks(tmp_path, monkeypatch):
    sqlite_path = tmp_path / "parts.sqlite3"
    _parts_database(sqlite_path, 2501)
    fake, output_dir, checkpoint_dir = _configure_fake_neogiga(tmp_path, monkeypatch)

    result = cli.run(
        [
            "--target",
            "neogiga",
            "--publish",
            "--pilot",
            "--yes",
            "--scale-import",
            "--scale-import-max",
            "3000",
            "--limit",
            "2501",
            "--batch-size",
            "5000",
            "--sqlite-file",
            str(sqlite_path),
            "--log-level",
            "ERROR",
        ]
    )

    assert result == 0
    assert [call[1]["source_rows_read"] for call in fake.calls] == [1000, 1000, 501]
    assert [call[1]["after_source_id"] for call in fake.calls] == [None, "C01000", "C02000"]
    checkpoint = CheckpointStore(checkpoint_dir / "jlcpcb_import_checkpoint.json").read()
    assert checkpoint.last_processed_key == "C02501"
    assert checkpoint.status == "completed"
    assert checkpoint.committed_batch_ids == ["batch-1", "batch-2", "batch-3"]
    report = json.loads((output_dir / "canonical_adapter_report.json").read_text())
    assert report["status"] == "completed"
    assert report["commit_row_limit"] == 1000
    assert report["rows_read"] == 2501
    assert set(fake.provenance) >= {
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


def test_neogiga_write_requires_explicit_yes(tmp_path, monkeypatch):
    sqlite_path = tmp_path / "parts.sqlite3"
    _parts_database(sqlite_path, 1)
    fake, _output_dir, _checkpoint_dir = _configure_fake_neogiga(tmp_path, monkeypatch)

    with pytest.raises(RuntimeError, match=r"--publish --pilot --yes"):
        cli.run(
            [
                "--target",
                "neogiga",
                "--publish",
                "--pilot",
                "--limit",
                "1",
                "--sqlite-file",
                str(sqlite_path),
                "--log-level",
                "ERROR",
            ]
        )

    assert fake.calls == []


def test_neogiga_chunk_with_transform_error_is_not_partially_committed(tmp_path, monkeypatch):
    sqlite_path = tmp_path / "parts.sqlite3"
    _parts_database(sqlite_path, 2)
    fake, _output_dir, _checkpoint_dir = _configure_fake_neogiga(tmp_path, monkeypatch)
    real_transform = cli.transform_record

    def fail_second_record(record, mapping, category_mapper):
        if record["lcsc"] == "C00002":
            raise ValueError("intentional transform failure")
        return real_transform(record, mapping, category_mapper)

    monkeypatch.setattr(cli, "transform_record", fail_second_record)

    with pytest.raises(RuntimeError, match=r"partially transformed NeoGiga chunk"):
        cli.run(
            [
                "--target",
                "neogiga",
                "--publish",
                "--pilot",
                "--yes",
                "--limit",
                "2",
                "--sqlite-file",
                str(sqlite_path),
                "--log-level",
                "ERROR",
            ]
        )

    assert fake.calls == []


def test_nonzero_exit_reports_already_committed_chunks_and_durable_cursor(tmp_path, monkeypatch):
    sqlite_path = tmp_path / "parts.sqlite3"
    _parts_database(sqlite_path, 1001)
    _fake, output_dir, checkpoint_dir = _configure_fake_neogiga(tmp_path, monkeypatch, fail_on_call=2)

    with pytest.raises(RuntimeError, match=r"committed batches are reported as \[batch-1\]"):
        cli.run(
            [
                "--target",
                "neogiga",
                "--publish",
                "--pilot",
                "--yes",
                "--scale-import",
                "--scale-import-max",
                "2000",
                "--limit",
                "1001",
                "--sqlite-file",
                str(sqlite_path),
                "--log-level",
                "ERROR",
            ]
        )

    report = json.loads((output_dir / "canonical_adapter_report.json").read_text())
    assert report["status"] == "failed"
    assert report["import_batch_ids"] == ["batch-1"]
    assert report["last_source_id"] == "C01000"
    checkpoint = CheckpointStore(checkpoint_dir / "jlcpcb_import_checkpoint.json").read()
    assert checkpoint.last_processed_key == "C01000"
    assert checkpoint.status == "running"
