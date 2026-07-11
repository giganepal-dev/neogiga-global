import sqlite3

from tools.jlcpcb_etl import cli


def test_dry_run_does_not_write_catalog(tmp_path, monkeypatch):
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
