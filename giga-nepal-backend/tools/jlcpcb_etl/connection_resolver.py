"""Safe PostgreSQL connection resolution for Laravel-hosted ETL runs."""

from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import quote


class ConnectionResolutionError(RuntimeError):
    pass


@dataclass(frozen=True)
class ResolvedConnection:
    dsn: str
    source: str

    @property
    def redacted(self) -> str:
        return redact_dsn(self.dsn)


def redact_dsn(dsn: str | None) -> str | None:
    if not dsn:
        return None
    if "@" not in dsn:
        return dsn
    prefix, suffix = dsn.rsplit("@", 1)
    scheme = prefix.split("://", 1)[0] if "://" in prefix else "postgresql"
    return f"{scheme}://***:***@{suffix}"


def parse_laravel_env(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}
    if not path.exists():
        return values
    for raw_line in path.read_text(encoding="utf-8", errors="ignore").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        value = value.strip()
        if (value.startswith('"') and value.endswith('"')) or (value.startswith("'") and value.endswith("'")):
            value = value[1:-1]
        values[key.strip()] = value
    return values


def build_postgres_dsn(values: dict[str, str]) -> str:
    driver = values.get("DB_CONNECTION", "pgsql")
    if driver not in {"pgsql", "postgres", "postgresql"}:
        raise ConnectionResolutionError(f"Unsupported production database driver: {driver}")
    host = values.get("DB_HOST") or "127.0.0.1"
    port = values.get("DB_PORT") or "5432"
    database = values.get("DB_DATABASE")
    username = values.get("DB_USERNAME")
    password = values.get("DB_PASSWORD")
    sslmode = values.get("DB_SSLMODE")
    if not database or not username:
        raise ConnectionResolutionError("Missing DB_DATABASE or DB_USERNAME")
    if password is None:
        raise ConnectionResolutionError("Missing DB_PASSWORD")
    auth = quote(username, safe="")
    if password:
        auth += ":" + quote(password, safe="")
    query = f"?sslmode={quote(sslmode, safe='')}" if sslmode else ""
    return f"postgresql://{auth}@{host}:{port}/{quote(database, safe='')}{query}"


def resolve_connection(
    *,
    database_url: str | None = None,
    laravel_base_path: Path | None = None,
    cli_dsn: str | None = None,
) -> ResolvedConnection:
    if database_url or os.environ.get("DATABASE_URL"):
        return ResolvedConnection(database_url or os.environ["DATABASE_URL"], "DATABASE_URL")
    if laravel_base_path:
        values = parse_laravel_env(laravel_base_path / ".env")
        if values:
            return ResolvedConnection(build_postgres_dsn(values), "LARAVEL_ENV")
    if cli_dsn:
        return ResolvedConnection(cli_dsn, "CLI")
    raise ConnectionResolutionError("No PostgreSQL connection could be resolved")
