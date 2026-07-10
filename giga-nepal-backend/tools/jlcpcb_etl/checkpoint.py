"""Atomic resumable import checkpoints."""

from __future__ import annotations

import json
import os
from dataclasses import asdict, dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


@dataclass
class ImportCheckpoint:
    source_checksum: str
    import_batch_id: str | None
    source_table: str | None
    last_processed_key: str | int | None
    rows_read: int = 0
    rows_loaded: int = 0
    rows_skipped: int = 0
    timestamp: str = ""

    def to_dict(self) -> dict[str, Any]:
        data = asdict(self)
        data["timestamp"] = self.timestamp or datetime.now(timezone.utc).isoformat()
        return data


class CheckpointStore:
    def __init__(self, path: Path | str) -> None:
        self.path = Path(path)

    def read(self) -> ImportCheckpoint | None:
        if not self.path.exists():
            return None
        with self.path.open("r", encoding="utf-8") as handle:
            data = json.load(handle)
        return ImportCheckpoint(**data)

    def write(self, checkpoint: ImportCheckpoint) -> None:
        self.path.parent.mkdir(parents=True, exist_ok=True)
        tmp = self.path.with_suffix(self.path.suffix + ".tmp")
        with tmp.open("w", encoding="utf-8") as handle:
            json.dump(checkpoint.to_dict(), handle, indent=2, sort_keys=True)
        os.replace(tmp, self.path)

    def reset(self) -> None:
        if self.path.exists():
            self.path.unlink()

    def can_resume(self, source_checksum: str) -> bool:
        current = self.read()
        return bool(current and current.source_checksum == source_checksum)
