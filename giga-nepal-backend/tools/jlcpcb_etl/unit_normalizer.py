"""Attribute unit parsing with Decimal arithmetic."""

from __future__ import annotations

import re
from dataclasses import dataclass
from decimal import Decimal, InvalidOperation


@dataclass(frozen=True)
class UnitParseResult:
    raw_value: str
    normalized_value: Decimal | None
    normalized_unit: str | None
    raw_unit: str | None = None
    error: str | None = None

    @property
    def ok(self) -> bool:
        return self.error is None

    def as_jsonable(self) -> dict[str, str | int | float | None]:
        value: str | None = None
        if self.normalized_value is not None:
            value = format(self.normalized_value.normalize(), "f")
        data: dict[str, str | int | float | None] = {
            "raw_value": self.raw_value,
            "normalized_value": value,
            "normalized_unit": self.normalized_unit,
        }
        if self.raw_unit:
            data["raw_unit"] = self.raw_unit
        if self.error:
            data["parse_error"] = self.error
        return data


_NUMBER_UNIT_RE = re.compile(
    r"^\s*(?P<number>[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?)\s*(?P<unit>[a-zA-ZµμΩ°%]+)\s*$",
    re.IGNORECASE,
)

_UNIT_FACTORS: dict[str, tuple[str, Decimal]] = {
    "pf": ("pF", Decimal("1")),
    "nf": ("pF", Decimal("1000")),
    "uf": ("pF", Decimal("1000000")),
    "µf": ("pF", Decimal("1000000")),
    "μf": ("pF", Decimal("1000000")),
    "mf": ("pF", Decimal("1000000000")),
    "f": ("pF", Decimal("1000000000000")),
    "ω": ("Ω", Decimal("1")),
    "ohm": ("Ω", Decimal("1")),
    "ohms": ("Ω", Decimal("1")),
    "kω": ("Ω", Decimal("1000")),
    "kohm": ("Ω", Decimal("1000")),
    "mv": ("V", Decimal("0.001")),
    "v": ("V", Decimal("1")),
    "kv": ("V", Decimal("1000")),
    "ma": ("A", Decimal("0.001")),
    "a": ("A", Decimal("1")),
    "ka": ("A", Decimal("1000")),
    "mw": ("W", Decimal("0.001")),
    "w": ("W", Decimal("1")),
    "kw": ("W", Decimal("1000")),
    "hz": ("Hz", Decimal("1")),
    "khz": ("Hz", Decimal("1000")),
    "mhz": ("Hz", Decimal("1000000")),
    "ghz": ("Hz", Decimal("1000000000")),
    "nh": ("H", Decimal("0.000000001")),
    "uh": ("H", Decimal("0.000001")),
    "µh": ("H", Decimal("0.000001")),
    "mh": ("H", Decimal("0.001")),
    "h": ("H", Decimal("1")),
    "°c": ("°C", Decimal("1")),
    "c": ("°C", Decimal("1")),
    "%": ("percent", Decimal("1")),
}

_CASE_SENSITIVE_UNITS: dict[str, tuple[str, Decimal]] = {
    "mΩ": ("Ω", Decimal("0.001")),
    "mOhm": ("Ω", Decimal("0.001")),
    "MΩ": ("Ω", Decimal("1000000")),
    "MOhm": ("Ω", Decimal("1000000")),
    "kΩ": ("Ω", Decimal("1000")),
    "kOhm": ("Ω", Decimal("1000")),
}


def normalize_unit_value(raw_value: str | int | float | Decimal | None) -> UnitParseResult:
    raw = "" if raw_value is None else str(raw_value).strip()
    if not raw:
        return UnitParseResult(raw_value=raw, normalized_value=None, normalized_unit=None, error="empty value")
    if any(token in raw for token in ("~", "/", " to ", "±")):
        return UnitParseResult(raw_value=raw, normalized_value=None, normalized_unit=None, error="ambiguous range or tolerance")
    match = _NUMBER_UNIT_RE.match(raw)
    if not match:
        return UnitParseResult(raw_value=raw, normalized_value=None, normalized_unit=None, error="unrecognized number/unit")
    unit = match.group("unit")
    if unit in _CASE_SENSITIVE_UNITS:
        normalized_unit, factor = _CASE_SENSITIVE_UNITS[unit]
    else:
        unit_key = unit.casefold()
        if unit_key not in _UNIT_FACTORS:
            return UnitParseResult(raw_value=raw, normalized_value=None, normalized_unit=None, raw_unit=unit, error="unsupported unit")
        normalized_unit, factor = _UNIT_FACTORS[unit_key]
    try:
        number = Decimal(match.group("number"))
    except InvalidOperation:
        return UnitParseResult(raw_value=raw, normalized_value=None, normalized_unit=None, raw_unit=unit, error="invalid number")
    return UnitParseResult(
        raw_value=raw,
        raw_unit=unit,
        normalized_value=number * factor,
        normalized_unit=normalized_unit,
    )


def normalize_attribute_value(attribute_name: str, raw_value: object) -> dict[str, object]:
    result = normalize_unit_value(None if raw_value is None else str(raw_value))
    payload = result.as_jsonable()
    payload["source_attribute"] = attribute_name
    return payload
