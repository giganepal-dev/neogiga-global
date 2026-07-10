"""Map source categories into the NeoGiga hierarchy."""

from __future__ import annotations

import re
from dataclasses import dataclass
from pathlib import Path

import yaml


UNKNOWN_PATH = "Uncategorized/Needs Review"


@dataclass(frozen=True)
class CategoryMapping:
    source_category_id: str | None
    source_name: str
    path: str
    is_unknown: bool = False

    @property
    def name(self) -> str:
        return self.path.split("/")[-1]


def normalize_category_key(value: str | None) -> str:
    return re.sub(r"[^a-z0-9]+", " ", (value or "").casefold()).strip()


class CategoryMapper:
    def __init__(self, mapping_file: Path | str | None = None) -> None:
        self.mapping_file = Path(mapping_file) if mapping_file else Path(__file__).with_name("mappings") / "categories.yaml"
        self._mapping = self._load_mapping()

    def _load_mapping(self) -> dict[str, str]:
        if not self.mapping_file.exists():
            return {}
        with self.mapping_file.open("r", encoding="utf-8") as handle:
            data = yaml.safe_load(handle) or {}
        raw = data.get("mappings", data)
        mapping: dict[str, str] = {}
        for key, value in raw.items():
            mapping[normalize_category_key(str(key))] = str(value).strip("/")
        return mapping

    def map_category(self, source_name: str | None, source_category_id: str | None = None) -> CategoryMapping:
        key = normalize_category_key(source_name)
        if key and key in self._mapping:
            return CategoryMapping(source_category_id, source_name or "", self._mapping[key], False)
        return CategoryMapping(source_category_id, source_name or "", UNKNOWN_PATH, True)

    @staticmethod
    def ancestors(path: str) -> list[str]:
        pieces = [piece.strip() for piece in path.split("/") if piece.strip()]
        return ["/".join(pieces[: index + 1]) for index in range(len(pieces))]
