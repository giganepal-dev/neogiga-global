"""Manufacturer display-name preservation and conservative matching."""

from __future__ import annotations

import re
import unicodedata
from dataclasses import dataclass


DEFAULT_ALIASES = {
    "ti": "texas instruments",
    "texas instruments inc": "texas instruments",
    "texas instruments incorporated": "texas instruments",
    "adi": "analog devices",
    "analog devices inc": "analog devices",
    "stmicroelectronics": "stmicroelectronics",
    "stmicro electronics": "stmicroelectronics",
}

_SUFFIXES = (
    "incorporated",
    "inc",
    "limited",
    "ltd",
    "llc",
    "corporation",
    "corp",
    "company",
    "co",
)


@dataclass(frozen=True)
class ManufacturerName:
    display_name: str
    normalized_name: str


def _clean_text(value: str) -> str:
    normalized = unicodedata.normalize("NFKC", value or "")
    normalized = normalized.replace("&", " and ")
    normalized = re.sub(r"[\.,]", " ", normalized)
    normalized = re.sub(r"\s+", " ", normalized).strip()
    return normalized


def normalize_manufacturer_name(
    value: str,
    aliases: dict[str, str] | None = None,
) -> ManufacturerName:
    display_name = _clean_text(value)
    lowered = display_name.casefold()
    lowered = re.sub(r"\s+", " ", lowered).strip()
    alias_map = {**DEFAULT_ALIASES, **(aliases or {})}
    if lowered in alias_map:
        return ManufacturerName(display_name=display_name, normalized_name=alias_map[lowered])

    words = lowered.split()
    while words and words[-1] in _SUFFIXES:
        words.pop()
    normalized = " ".join(words).strip()
    normalized = alias_map.get(normalized, normalized)
    return ManufacturerName(display_name=display_name, normalized_name=normalized)


def normalize_mpn(value: str | None) -> str:
    return re.sub(r"\s+", "", unicodedata.normalize("NFKC", value or "")).upper()
