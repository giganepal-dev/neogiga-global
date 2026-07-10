from pathlib import Path

import pytest

from tools.jlcpcb_etl.connection_resolver import (
    ConnectionResolutionError,
    build_postgres_dsn,
    redact_dsn,
    resolve_connection,
)


def test_database_url_takes_precedence(tmp_path, monkeypatch):
    (tmp_path / ".env").write_text(
        "DB_CONNECTION=pgsql\nDB_HOST=localhost\nDB_DATABASE=laravel\nDB_USERNAME=laravel\nDB_PASSWORD=secret\n",
        encoding="utf-8",
    )
    monkeypatch.setenv("DATABASE_URL", "postgresql://env:secret@example.com/db")

    resolved = resolve_connection(laravel_base_path=tmp_path)

    assert resolved.source == "DATABASE_URL"
    assert resolved.dsn == "postgresql://env:secret@example.com/db"


def test_laravel_env_fallback_url_encodes_special_password(tmp_path, monkeypatch):
    monkeypatch.delenv("DATABASE_URL", raising=False)
    (tmp_path / ".env").write_text(
        "DB_CONNECTION=pgsql\nDB_HOST=127.0.0.1\nDB_PORT=5432\nDB_DATABASE=neo giga\nDB_USERNAME=neo@giga\nDB_PASSWORD=p@ss word:/?#\nDB_SSLMODE=require\n",
        encoding="utf-8",
    )

    resolved = resolve_connection(laravel_base_path=tmp_path)

    assert resolved.source == "LARAVEL_ENV"
    assert "neo%40giga" in resolved.dsn
    assert "p%40ss%20word%3A%2F%3F%23" in resolved.dsn
    assert "sslmode=require" in resolved.dsn


def test_missing_password_is_rejected():
    with pytest.raises(ConnectionResolutionError, match="Missing DB_PASSWORD"):
        build_postgres_dsn({"DB_CONNECTION": "pgsql", "DB_DATABASE": "neogiga", "DB_USERNAME": "neogiga"})


def test_redacted_dsn_hides_credentials():
    redacted = redact_dsn("postgresql://user:secret@example.com:5432/neogiga")

    assert redacted == "postgresql://***:***@example.com:5432/neogiga"
    assert "secret" not in redacted


def test_invalid_driver_is_rejected():
    with pytest.raises(ConnectionResolutionError, match="Unsupported production database driver"):
        build_postgres_dsn({"DB_CONNECTION": "mysql", "DB_DATABASE": "neogiga", "DB_USERNAME": "u", "DB_PASSWORD": "p"})


def test_cli_dsn_used_after_missing_env(monkeypatch):
    monkeypatch.delenv("DATABASE_URL", raising=False)

    resolved = resolve_connection(laravel_base_path=Path("/path/that/does/not/exist"), cli_dsn="postgresql://u:p@localhost/db")

    assert resolved.source == "CLI"
