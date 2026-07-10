"""Configuration for the JLCPCB ETL."""

from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path


PACKAGE_DIR = Path(__file__).resolve().parent
OUTPUT_DIR = PACKAGE_DIR / "output"
CHECKPOINT_DIR = PACKAGE_DIR / "checkpoints"


@dataclass(frozen=True)
class Settings:
    database_url: str | None
    sqlite_url_override: str | None
    output_dir: Path = OUTPUT_DIR
    checkpoint_dir: Path = CHECKPOINT_DIR
    batch_size: int = 5000

    @classmethod
    def from_env(cls, batch_size: int = 5000) -> "Settings":
        return cls(
            database_url=os.environ.get("DATABASE_URL"),
            sqlite_url_override=os.environ.get("JLCPCB_SQLITE_URL"),
            batch_size=batch_size,
        )

    def ensure_dirs(self) -> None:
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.checkpoint_dir.mkdir(parents=True, exist_ok=True)


def redact_database_url(url: str | None) -> str | None:
    if not url:
        return None
    if "@" not in url:
        return url
    prefix, suffix = url.rsplit("@", 1)
    scheme = prefix.split("://", 1)[0] if "://" in prefix else "postgresql"
    return f"{scheme}://***:***@{suffix}"
