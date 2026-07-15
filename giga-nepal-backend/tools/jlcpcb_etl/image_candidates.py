"""Stage JLCPCB image URLs for manual rights review.

This command never downloads or activates an image. It reads candidate metadata
from the already-acquired SQLite snapshot and stores only hidden review rows.
"""

from __future__ import annotations

import argparse
import json
import re
import sqlite3
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Iterable, Iterator
from urllib.parse import urlsplit, urlunsplit

import psycopg
from psycopg.rows import dict_row

from .canonical_adapter import SOURCE_CODE
from .connection_resolver import resolve_connection


SOURCE_NAME = "CDFER jlcpcb-parts-database snapshot"
SOURCE_URL = "https://github.com/CDFER/jlcpcb-parts-database"
SOURCE_PAGE_URL = "https://cdfer.github.io/jlcpcb-parts-database/"
ALLOWED_IMAGE_HOST = "assets.lcsc.com"
ALLOWED_IMAGE_PATH_PREFIX = "/images/lcsc/900x900/"
DISCOVERED_BY = "jlcpcb-images:stage-candidates"
RIGHTS_BASIS = (
    "No image redistribution authorization is recorded. Manual rights review is required before any fetch or use."
)
LICENSE_NOTE = (
    "The CDFER repository is MIT-licensed; that does not establish redistribution rights for third-party product "
    "images. Candidate URL only; the asset was not requested, downloaded, hotlinked, or activated."
)
CONFIDENCE_LEVEL = "source-provided URL; asset and rights unverified"
REQUIRED_TARGET_COLUMNS = {
    "product_id",
    "candidate_url",
    "source_page_url",
    "source_name",
    "source_url",
    "source_file",
    "source_part_id",
    "source_checksum",
    "discovered_by",
    "downloaded_at",
    "imported_at",
    "data_year",
    "license_note",
    "confidence_level",
    "confidence_score",
    "original_raw_value",
    "normalized_value",
    "evidence",
    "discovered_at",
    "created_at",
    "updated_at",
    "rights_status",
    "rights_basis",
    "rights_review_required",
    "is_active",
    "image_role",
    "pixel_width",
    "pixel_height",
    "asset_fetch_status",
}


@dataclass(frozen=True)
class ImageCandidate:
    source_part_id: str
    candidate_url: str
    source_page_url: str
    image_role: str
    image_index: int
    original_image: dict[str, Any]


@dataclass(frozen=True)
class SelectionResult:
    candidate: ImageCandidate | None
    reason: str


UPSERT_SQL = """
    INSERT INTO product_image_candidates (
      product_id, candidate_url, source_page_url, source_name, source_url,
      source_file, source_part_id, discovered_by, rights_status, rights_basis,
      rights_review_required, is_active, confidence_score, confidence_level,
      image_role, pixel_width, pixel_height, asset_fetch_status, source_checksum,
      downloaded_at, imported_at, data_year, license_note, original_raw_value,
      normalized_value, evidence, discovered_at, created_at, updated_at
    )
    VALUES (
      %s, %s, %s, %s, %s,
      %s, %s, 'jlcpcb-images:stage-candidates', 'pending_review', %s,
      true, false, 70.00, %s,
      %s, 900, 900, 'not_requested', %s,
      %s, %s, %s, %s, %s::json,
      %s, %s::json, now(), now(), now()
    )
    ON CONFLICT (product_id, candidate_url) DO NOTHING
"""


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Stage hidden JLCPCB image URL candidates for manual rights review (dry-run by default)"
    )
    parser.add_argument("--sqlite-file", type=Path, required=True, help="Existing jlcpcb-components.sqlite3 snapshot")
    parser.add_argument("--limit", type=int, default=None, help="Maximum source rows to scan")
    parser.add_argument("--offset", type=int, default=0, help="Zero-based source row offset")
    parser.add_argument("--batch-size", type=int, default=1000, help="Source-link lookup/upsert batch size")
    mode = parser.add_mutually_exclusive_group()
    mode.add_argument("--dry-run", action="store_true", help="Report candidate upserts without writing (the default)")
    mode.add_argument("--apply", action="store_true", help="Write hidden pending-review candidate rows")
    parser.add_argument("--yes", action="store_true", help="Required together with --apply")
    parser.add_argument("--source-checksum", default=None, help="Optional verified SHA-256 for the SQLite snapshot")
    parser.add_argument(
        "--source-downloaded-at",
        default=None,
        help="ISO-8601 source-snapshot acquisition time; defaults to the SQLite file timestamp",
    )
    parser.add_argument("--data-year", type=int, default=None, help="Source data year; defaults to acquisition year")
    parser.add_argument("--connection-dsn", default=None, help="Development-only PostgreSQL DSN override")
    parser.add_argument(
        "--laravel-base-path",
        type=Path,
        default=Path.cwd(),
        help="Laravel base path containing .env",
    )
    return parser


def normalize_candidate_url(value: Any) -> str | None:
    if not isinstance(value, str) or not value.strip():
        return None
    try:
        parsed = urlsplit(value.strip())
        port = parsed.port
    except ValueError:
        return None
    if parsed.scheme.casefold() != "https":
        return None
    if (parsed.hostname or "").casefold() != ALLOWED_IMAGE_HOST:
        return None
    if parsed.username or parsed.password or port not in (None, 443):
        return None
    if parsed.query or parsed.fragment:
        return None
    if not parsed.path.startswith(ALLOWED_IMAGE_PATH_PREFIX):
        return None
    if ".." in parsed.path.split("/"):
        return None
    if Path(parsed.path).suffix.casefold() not in {".jpg", ".jpeg", ".png", ".webp"}:
        return None
    return urlunsplit(("https", ALLOWED_IMAGE_HOST, parsed.path, "", ""))


def _normalize_source_page_url(value: Any) -> str:
    if not isinstance(value, str) or not value.strip():
        return SOURCE_PAGE_URL
    try:
        parsed = urlsplit(value.strip())
        port = parsed.port
    except ValueError:
        return SOURCE_PAGE_URL
    if (
        parsed.scheme.casefold() != "https"
        or (parsed.hostname or "").casefold() not in {"lcsc.com", "www.lcsc.com"}
        or parsed.username
        or parsed.password
        or port not in (None, 443)
    ):
        return SOURCE_PAGE_URL
    return urlunsplit(("https", parsed.hostname.casefold(), parsed.path, "", ""))


def _is_front_image(url: str) -> bool:
    filename = Path(urlsplit(url).path).name.casefold()
    return re.search(r"(?:^|[_-])front(?:[._-]|$)", filename) is not None


def select_preferred_candidate(source_part_id: str, raw_extra: Any) -> SelectionResult:
    try:
        if isinstance(raw_extra, bytes):
            raw_extra = raw_extra.decode("utf-8")
        payload = raw_extra if isinstance(raw_extra, dict) else json.loads(str(raw_extra))
    except (UnicodeDecodeError, TypeError, ValueError, json.JSONDecodeError):
        return SelectionResult(None, "invalid_extra")
    if not isinstance(payload, dict):
        return SelectionResult(None, "invalid_extra")

    images = payload.get("images")
    if not isinstance(images, list) or not images:
        return SelectionResult(None, "no_images")

    eligible: list[tuple[int, int, str, dict[str, Any]]] = []
    saw_900 = False
    for index, image in enumerate(images):
        if not isinstance(image, dict):
            continue
        raw_url = image.get("900x900")
        if raw_url not in (None, ""):
            saw_900 = True
        normalized = normalize_candidate_url(raw_url)
        if normalized is None:
            continue
        front_rank = 0 if _is_front_image(normalized) else 1
        eligible.append((front_rank, index, normalized, image))

    if not eligible:
        return SelectionResult(None, "disallowed_url" if saw_900 else "no_900_image")

    front_rank, image_index, normalized_url, original_image = min(eligible, key=lambda item: (item[0], item[1]))
    image_role = "front" if front_rank == 0 else "other"
    candidate = ImageCandidate(
        source_part_id=str(source_part_id).strip(),
        candidate_url=normalized_url,
        source_page_url=_normalize_source_page_url(payload.get("url")),
        image_role=image_role,
        image_index=image_index,
        original_image=original_image,
    )
    return SelectionResult(candidate, "selected_front" if image_role == "front" else "selected_fallback")


def stream_component_rows(
    sqlite_path: Path,
    *,
    limit: int | None = None,
    offset: int = 0,
) -> Iterator[tuple[str, Any]]:
    path = sqlite_path.expanduser().resolve()
    if not path.is_file():
        raise RuntimeError(f"SQLite source file does not exist: {path}")
    conn = sqlite3.connect(path.as_uri() + "?mode=ro", uri=True)
    conn.row_factory = sqlite3.Row
    try:
        columns = {row["name"] for row in conn.execute('PRAGMA table_info("components")')}
        if not {"lcsc", "extra"}.issubset(columns):
            raise RuntimeError("SQLite components table must contain lcsc and extra columns")
        sql = 'SELECT lcsc, extra FROM "components" ORDER BY lcsc'
        params: list[int] = []
        if limit is not None:
            sql += " LIMIT ?"
            params.append(limit)
        elif offset:
            sql += " LIMIT -1"
        if offset:
            sql += " OFFSET ?"
            params.append(offset)
        for row in conn.execute(sql, params):
            source_part_id = str(row["lcsc"] or "").strip()
            if source_part_id:
                yield source_part_id, row["extra"]
    finally:
        conn.close()


def _resolve_product_ids(conn: Any, source_part_ids: list[str]) -> dict[str, int]:
    if not source_part_ids:
        return {}
    rows = conn.execute(
        """
        SELECT cps.source_part_id, cps.product_id
        FROM catalog_product_sources cps
        JOIN catalog_sources cs ON cs.id = cps.source_id
        WHERE cs.code = %s AND cps.source_part_id = ANY(%s)
        """,
        (SOURCE_CODE, source_part_ids),
    ).fetchall()
    return {str(row["source_part_id"]): int(row["product_id"]) for row in rows}


def _verify_target_schema(conn: Any) -> None:
    rows = conn.execute(
        """
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = ANY(current_schemas(false))
          AND table_name = 'product_image_candidates'
        """
    ).fetchall()
    available = {str(row["column_name"]) for row in rows}
    missing = sorted(REQUIRED_TARGET_COLUMNS - available)
    if missing:
        raise RuntimeError(
            "product_image_candidates staging migration is not applied; missing columns: " + ", ".join(missing)
        )


def _acquire_lock(conn: Any) -> None:
    row = conn.execute(
        "SELECT pg_try_advisory_lock(hashtext('jlcpcb_image_candidate_staging')) AS locked"
    ).fetchone()
    if not row or not bool(row["locked"]):
        raise RuntimeError("Another JLCPCB image-candidate staging run appears to be active")


def _release_lock(conn: Any) -> None:
    conn.execute("SELECT pg_advisory_unlock(hashtext('jlcpcb_image_candidate_staging'))")


def _upsert_candidates(conn: Any, parameters: list[tuple[Any, ...]]) -> None:
    if not parameters:
        return
    with conn.transaction():
        with conn.cursor() as cursor:
            cursor.executemany(UPSERT_SQL, parameters)


def _candidate_parameters(
    candidate: ImageCandidate,
    *,
    product_id: int,
    source_file: str,
    source_checksum: str | None,
    source_downloaded_at: datetime,
    imported_at: datetime,
    data_year: int,
) -> tuple[Any, ...]:
    evidence = {
        "asset_fetch_status": "not_requested",
        "source_field": "components.extra.images",
        "selection_key": "900x900",
        "selection_index": candidate.image_index,
        "selection_role": candidate.image_role,
        "allowlist": {"scheme": "https", "host": ALLOWED_IMAGE_HOST},
        "source_downloaded_at": source_downloaded_at.isoformat(),
        "rights_review": "manual review required before any asset fetch or public use",
    }
    return (
        product_id,
        candidate.candidate_url,
        candidate.source_page_url,
        SOURCE_NAME,
        SOURCE_URL,
        source_file,
        candidate.source_part_id,
        RIGHTS_BASIS,
        CONFIDENCE_LEVEL,
        candidate.image_role,
        source_checksum,
        source_downloaded_at,
        imported_at,
        data_year,
        LICENSE_NOTE,
        json.dumps(candidate.original_image, sort_keys=True, default=str),
        candidate.candidate_url,
        json.dumps(evidence, sort_keys=True),
    )


def _parse_timestamp(value: str | None, sqlite_path: Path) -> datetime:
    if value:
        normalized = value.strip().replace("Z", "+00:00")
        try:
            parsed = datetime.fromisoformat(normalized)
        except ValueError as exc:
            raise RuntimeError("--source-downloaded-at must be a valid ISO-8601 timestamp") from exc
        if parsed.tzinfo is None:
            parsed = parsed.replace(tzinfo=timezone.utc)
        return parsed.astimezone(timezone.utc)
    return datetime.fromtimestamp(sqlite_path.stat().st_mtime, tz=timezone.utc)


def _validate_checksum(value: str | None) -> str | None:
    if value is None:
        return None
    checksum = value.strip().casefold()
    if not re.fullmatch(r"[0-9a-f]{64}", checksum):
        raise RuntimeError("--source-checksum must be a 64-character SHA-256 hex digest")
    return checksum


def _connect_database(dsn: str):
    return psycopg.connect(dsn, row_factory=dict_row, autocommit=True)


def stage_candidates(
    conn: Any,
    rows: Iterable[tuple[str, Any]],
    *,
    apply: bool,
    batch_size: int,
    source_file: str,
    source_checksum: str | None,
    source_downloaded_at: datetime,
    data_year: int,
) -> dict[str, Any]:
    result: dict[str, Any] = {
        "mode": "apply" if apply else "dry_run",
        "rows_scanned": 0,
        "candidate_rows": 0,
        "front_selected": 0,
        "fallback_selected": 0,
        "invalid_extra": 0,
        "no_images": 0,
        "no_900_image": 0,
        "disallowed_url": 0,
        "source_links_matched": 0,
        "source_links_missing": 0,
        "upserts_planned": 0,
        "upserts_applied": 0,
        "asset_downloads": 0,
        "public_activations": 0,
    }
    imported_at = datetime.now(timezone.utc)
    pending: list[ImageCandidate] = []

    def flush() -> None:
        if not pending:
            return
        product_ids = _resolve_product_ids(conn, [candidate.source_part_id for candidate in pending])
        parameters: list[tuple[Any, ...]] = []
        for candidate in pending:
            product_id = product_ids.get(candidate.source_part_id)
            if product_id is None:
                result["source_links_missing"] += 1
                continue
            result["source_links_matched"] += 1
            parameters.append(
                _candidate_parameters(
                    candidate,
                    product_id=product_id,
                    source_file=source_file,
                    source_checksum=source_checksum,
                    source_downloaded_at=source_downloaded_at,
                    imported_at=imported_at,
                    data_year=data_year,
                )
            )
        result["upserts_planned"] += len(parameters)
        if apply:
            _upsert_candidates(conn, parameters)
            result["upserts_applied"] += len(parameters)
        pending.clear()

    for source_part_id, raw_extra in rows:
        result["rows_scanned"] += 1
        selection = select_preferred_candidate(source_part_id, raw_extra)
        if selection.candidate is None:
            result[selection.reason] += 1
            continue
        result["candidate_rows"] += 1
        if selection.reason == "selected_front":
            result["front_selected"] += 1
        else:
            result["fallback_selected"] += 1
        pending.append(selection.candidate)
        if len(pending) >= batch_size:
            flush()
    flush()
    return result


def run(argv: list[str] | None = None) -> int:
    args = build_parser().parse_args(argv)
    if args.limit is not None and args.limit < 1:
        raise RuntimeError("--limit must be at least 1")
    if args.offset < 0:
        raise RuntimeError("--offset cannot be negative")
    if args.batch_size < 1 or args.batch_size > 10000:
        raise RuntimeError("--batch-size must be between 1 and 10000")
    if args.apply and not args.yes:
        raise RuntimeError("Refusing candidate writes without --apply --yes")

    sqlite_path = args.sqlite_file.expanduser().resolve()
    if not sqlite_path.is_file():
        raise RuntimeError(f"SQLite source file does not exist: {sqlite_path}")
    source_downloaded_at = _parse_timestamp(args.source_downloaded_at, sqlite_path)
    data_year = args.data_year if args.data_year is not None else source_downloaded_at.year
    if data_year < 2000 or data_year > datetime.now(timezone.utc).year + 1:
        raise RuntimeError("--data-year is outside the accepted source-data range")
    source_checksum = _validate_checksum(args.source_checksum)
    resolved = resolve_connection(
        laravel_base_path=args.laravel_base_path.expanduser().resolve(),
        cli_dsn=args.connection_dsn,
    )

    with _connect_database(resolved.dsn) as conn:
        locked = False
        try:
            if args.apply:
                _verify_target_schema(conn)
                _acquire_lock(conn)
                locked = True
            result = stage_candidates(
                conn,
                stream_component_rows(sqlite_path, limit=args.limit, offset=args.offset),
                apply=args.apply,
                batch_size=args.batch_size,
                source_file=str(sqlite_path),
                source_checksum=source_checksum,
                source_downloaded_at=source_downloaded_at,
                data_year=data_year,
            )
        finally:
            if locked:
                _release_lock(conn)

    result.update(
        {
            "source_file": str(sqlite_path),
            "source_name": SOURCE_NAME,
            "source_url": SOURCE_URL,
            "source_page_url": SOURCE_PAGE_URL,
            "downloaded_at": source_downloaded_at.isoformat(),
            "imported_at": datetime.now(timezone.utc).isoformat() if args.apply else None,
            "data_year": data_year,
            "license_note": LICENSE_NOTE,
            "confidence_level": CONFIDENCE_LEVEL,
            "original_raw_value": "components.extra.images (retained per candidate on apply)",
            "normalized_value": "allowlisted preferred 900x900 HTTPS URL (retained per candidate on apply)",
            "rights_status": "pending_review",
            "is_active": False,
        }
    )
    print(json.dumps(result, indent=2, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(run())
