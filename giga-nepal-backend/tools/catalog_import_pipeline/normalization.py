from __future__ import annotations

import hashlib
import json
import re
import unicodedata
from typing import Any


def normalize_text(value: Any) -> str:
    return re.sub(r"\s+", " ", unicodedata.normalize("NFKC", str(value or ""))).strip()


def normalize_mpn(value: Any) -> str:
    return re.sub(r"[\s\-_/]+", "", normalize_text(value)).upper()


def slugify(value: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", normalize_text(value).casefold()).strip("-")
    return slug or "item"


def stable_sku(manufacturer: str, mpn: str) -> str:
    digest = hashlib.sha1(f"{normalize_text(manufacturer).casefold()}::{normalize_mpn(mpn)}".encode("utf-8")).hexdigest()[:10].upper()
    return f"NG-{digest}"


def canonical_identity(manufacturer: str, mpn: str) -> str:
    return f"{normalize_text(manufacturer).casefold()}::{normalize_mpn(mpn)}"


def payload_checksum(payload: dict[str, Any]) -> str:
    return hashlib.sha256(json.dumps(payload, sort_keys=True, default=str).encode("utf-8")).hexdigest()


def bounded(value: str | None, length: int) -> str | None:
    text = normalize_text(value)
    if not text:
        return None
    return text if len(text) <= length else text[: max(0, length - 1)].rstrip(" |,-") + "…"

