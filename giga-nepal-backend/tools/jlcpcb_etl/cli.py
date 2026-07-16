"""Command line interface for the NeoGiga JLCPCB ETL."""

from __future__ import annotations

import argparse
import json
import os
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path
from time import perf_counter
from typing import Any
from uuid import uuid4

from .adapters import TargetAdapterRegistry
from .canonical_adapter import (
    AdapterResult,
    LICENSE_NOTE,
    MAX_COMMIT_ROWS,
    SOURCE_CODE,
    SOURCE_NAME,
    SOURCE_PAGE_URL,
    SOURCE_URL,
)
from .category_mapper import CategoryMapper
from .checkpoint import CheckpointStore, ImportCheckpoint
from .config import Settings, redact_database_url
from .connection_resolver import ConnectionResolutionError, resolve_connection
from .downloader import DownloadResult, discover_sqlite_url, download_sqlite, fetch_readme, sha256_file
from .loader import ensure_schema, load_batch, validate_connection
from .logging_config import configure_logging
from .schema_inspector import SourceMapping, inspect_sqlite_schema
from .sqlite_reader import stream_source_rows
from .transformer import TransformedPart, transform_record
from .validation import ValidationAccumulator, build_validation_report, write_validation_report


ADAPTER_COUNTER_FIELDS = (
    "rows_read",
    "rows_transformed",
    "products_inserted",
    "products_updated",
    "brands_inserted",
    "brands_matched",
    "categories_inserted",
    "categories_matched",
    "source_links_created",
    "source_links_updated",
    "source_aliases_created",
    "source_aliases_updated",
    "specifications_created",
    "specifications_updated",
    "offers_created",
    "offers_updated",
    "images_created",
    "skipped",
)


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Import open JLCPCB/LCSC parts database into PostgreSQL")
    parser.add_argument("--dry-run", action="store_true", help="Inspect and transform samples without database writes")
    parser.add_argument("--limit", type=int, default=None, help="Maximum rows to process")
    cursor = parser.add_mutually_exclusive_group()
    cursor.add_argument("--after-source-id", default=None, help="Resume after this stable source ID using an ordered keyset cursor")
    cursor.add_argument("--offset", type=int, default=None, help="Legacy row offset for inspection only; NeoGiga writes require keyset cursors")
    parser.add_argument("--batch-size", type=int, default=5000, help="Load batch size")
    parser.add_argument("--resume", action="store_true", help="Resume only if source checksum matches checkpoint")
    parser.add_argument(
        "--missing-only",
        action="store_true",
        help="For NeoGiga writes, scan a keyset range and import only source IDs not already linked",
    )
    parser.add_argument("--reset-checkpoint", action="store_true", help="Delete checkpoint before running")
    parser.add_argument("--sqlite-file", type=Path, default=None, help="Use an already downloaded SQLite file")
    parser.add_argument("--download-only", action="store_true", help="Download database and exit")
    parser.add_argument("--inspect-only", action="store_true", help="Inspect SQLite schema and exit")
    parser.add_argument("--validate-only", action="store_true", help="Run validation/dry-run flow only")
    parser.add_argument("--log-level", default="INFO", help="Logging level")
    parser.add_argument("--yes", action="store_true", help="Required for non-dry-run imports")
    parser.add_argument("--target", choices=["standalone", "neogiga"], default="standalone", help="Catalog write target")
    parser.add_argument("--publish", action="store_true", help="Allow writes for the selected target")
    parser.add_argument("--pilot", action="store_true", help="Mark this run as the controlled NeoGiga pilot import")
    parser.add_argument("--scale-import", action="store_true", help="Allow a guarded NeoGiga hidden/pending scale import above 1,000 rows")
    parser.add_argument("--scale-import-max", type=int, default=20000, help="Maximum NeoGiga rows allowed with --scale-import; default 20,000")
    parser.add_argument("--connection-check", action="store_true", help="Resolve the target database connection and run SELECT 1")
    parser.add_argument("--print-connection-source", action="store_true", help="Print only DATABASE_URL, LARAVEL_ENV, or CLI")
    parser.add_argument("--connection-dsn", default=None, help="Development-only PostgreSQL DSN override")
    parser.add_argument("--laravel-base-path", type=Path, default=Path.cwd(), help="Laravel base path containing .env")
    parser.add_argument("--rollback-batch", default=None, help="Prepare or execute source-scoped rollback for an import batch UUID")
    parser.add_argument("--no-search-index", action="store_true", help="Skip search-index rebuild hooks")
    parser.add_argument("--no-seo", action="store_true", help="Skip SEO/sitemap hooks")
    return parser


def _download_or_wrap(args: argparse.Namespace, settings: Settings) -> DownloadResult:
    if args.sqlite_file:
        path = args.sqlite_file.expanduser().resolve()
        checksum = sha256_file(path)
        return DownloadResult(path=path, source_url=f"file://{path}", checksum=checksum, size_bytes=path.stat().st_size, reused=True)
    return download_sqlite(settings.output_dir, override_url=settings.sqlite_url_override)


def _mapping_from_report(report: dict) -> SourceMapping:
    raw = report.get("detected_mapping")
    if not raw:
        raise RuntimeError("Could not infer required source fields from SQLite schema")
    mapping = SourceMapping(**raw)
    if not mapping.source_id:
        raise RuntimeError("Could not resolve stable source ID field")
    return mapping


def _resolve_neogiga_connection(args: argparse.Namespace, settings: Settings):
    try:
        return resolve_connection(
            database_url=settings.database_url,
            laravel_base_path=args.laravel_base_path.expanduser().resolve(),
            cli_dsn=args.connection_dsn,
        )
    except ConnectionResolutionError as exc:
        raise RuntimeError(f"NeoGiga PostgreSQL connection resolution failed: {exc}") from exc


def _atomic_write_text(path: Path, value: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(path.suffix + ".tmp")
    with tmp.open("w", encoding="utf-8") as handle:
        handle.write(value)
        handle.flush()
        os.fsync(handle.fileno())
    os.replace(tmp, path)
    directory_fd = os.open(path.parent, os.O_RDONLY)
    try:
        os.fsync(directory_fd)
    finally:
        os.close(directory_fd)


def _write_adapter_report(report: dict[str, Any], output_dir: Path) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)
    _atomic_write_text(
        output_dir / "canonical_adapter_report.json",
        json.dumps(report, indent=2, default=str) + "\n",
    )
    lines = [
        "# NeoGiga Canonical Adapter Report",
        "",
        f"- Status: {report['status']}",
        f"- Mode: {report['mode']}",
        f"- Run ID: {report['run_id']}",
        f"- Committed batches: {len(report['import_batch_ids'])}",
        f"- Last import batch: {report.get('import_batch_id')}",
        f"- Rows read: {report['rows_read']}",
        f"- Source rows scanned: {report['source_rows_scanned']}",
        f"- Source rows already linked: {report['source_rows_already_linked']}",
        f"- Products inserted: {report['products_inserted']}",
        f"- Products updated: {report['products_updated']}",
        f"- Brands inserted: {report['brands_inserted']}",
        f"- Categories inserted: {report['categories_inserted']}",
        f"- Source links created: {report['source_links_created']}",
        f"- Source links updated: {report['source_links_updated']}",
        f"- Canonical aliases created: {report['source_aliases_created']}",
        f"- Canonical aliases updated: {report['source_aliases_updated']}",
        f"- Specifications created: {report['specifications_created']}",
        f"- Specifications updated: {report['specifications_updated']}",
        f"- Offers created: {report['offers_created']}",
        f"- Offers updated: {report['offers_updated']}",
        f"- NeoGiga placeholder images created: {report['images_created']}",
        f"- Skipped: {report['skipped']}",
        "",
        "Full machine-readable report is in `canonical_adapter_report.json`.",
    ]
    _atomic_write_text(output_dir / "canonical_adapter_report.md", "\n".join(lines) + "\n")


def _source_provenance(result: DownloadResult, mapping: SourceMapping, imported_at: str) -> dict[str, Any]:
    source_mtime = datetime.fromtimestamp(result.path.stat().st_mtime, tz=timezone.utc)
    return {
        "source_name": SOURCE_NAME,
        "source_url": SOURCE_URL,
        "source_file": result.path.name,
        "source_page_url": SOURCE_PAGE_URL,
        "downloaded_at": source_mtime.isoformat(),
        "imported_at": imported_at,
        "data_year": str(source_mtime.year),
        "license_note": LICENSE_NOTE,
        "confidence_level": "source-provided-checksum-verified",
        "original_raw_value": {
            "path": str(result.path),
            "download_url": result.source_url,
            "sha256": result.checksum,
            "size_bytes": result.size_bytes,
            "downloaded_at_basis": "source_file_mtime",
        },
        "normalized_value": {
            "source_code": SOURCE_CODE,
            "parts_table": mapping.parts_table,
            "field_mapping": mapping.__dict__,
        },
    }


def _resolve_source_cursor(
    args: argparse.Namespace,
    checkpoint_store: CheckpointStore,
    *,
    source_checksum: str,
    source_table: str,
) -> tuple[str | None, int, ImportCheckpoint | None]:
    if not args.resume:
        return args.after_source_id, args.offset or 0, None
    checkpoint = checkpoint_store.read()
    if not checkpoint:
        raise RuntimeError("No checkpoint exists; refusing unsafe resume")
    if checkpoint.source_checksum != source_checksum:
        raise RuntimeError("Checkpoint checksum does not match current source; refusing unsafe resume")
    if checkpoint.source_table != source_table:
        raise RuntimeError("Checkpoint source table does not match current source; refusing unsafe resume")
    if checkpoint.target != args.target:
        raise RuntimeError("Checkpoint target does not match this import target; refusing unsafe resume")
    if not checkpoint.last_processed_key:
        raise RuntimeError("Checkpoint has no stable source cursor; refusing unsafe resume")
    if args.after_source_id is not None and str(args.after_source_id) != str(checkpoint.last_processed_key):
        raise RuntimeError("--after-source-id does not match checkpoint cursor; refusing unsafe resume")
    if args.target == "neogiga" and not checkpoint.import_batch_id:
        raise RuntimeError("NeoGiga checkpoint has no committed import batch; refusing unsafe resume")
    return str(checkpoint.last_processed_key), 0, checkpoint


def _new_adapter_run_report(
    *,
    run_id: str,
    mode: str,
    source_checksum: str,
    source_provenance: dict[str, Any],
    after_source_id: str | None,
    commit_row_limit: int,
) -> dict[str, Any]:
    report: dict[str, Any] = {
        "status": "running",
        "run_id": run_id,
        "mode": mode,
        "source_checksum": source_checksum,
        "source_provenance": source_provenance,
        "after_source_id": after_source_id,
        "last_source_id": after_source_id,
        "commit_row_limit": commit_row_limit,
        "import_batch_id": None,
        "import_batch_ids": [],
        "committed_batches": [],
        "errors": [],
        "failure": None,
        "source_rows_scanned": 0,
        "source_rows_already_linked": 0,
    }
    report.update({field: 0 for field in ADAPTER_COUNTER_FIELDS})
    return report


def _record_committed_batch(report: dict[str, Any], result: AdapterResult) -> None:
    if not result.import_batch_id:
        raise RuntimeError("Adapter returned a committed chunk without an import batch ID")
    report["import_batch_id"] = result.import_batch_id
    report["import_batch_ids"].append(result.import_batch_id)
    report["last_source_id"] = result.last_source_id
    for field in ADAPTER_COUNTER_FIELDS:
        report[field] += getattr(result, field)
    report["errors"].extend(result.errors)
    report["committed_batches"].append(result.__dict__.copy())


def _source_key(record: dict[str, object], mapping: SourceMapping) -> str:
    raw = record.get(mapping.source_id)
    key = str(raw).strip() if raw is not None else ""
    if not key:
        raise RuntimeError("Source row has no stable source ID; refusing an uncheckpointable import")
    return key


def run(argv: list[str] | None = None) -> int:
    args = build_parser().parse_args(argv)
    if args.limit is not None and args.limit < 1:
        raise RuntimeError("--limit must be at least 1")
    if args.offset is not None and args.offset < 0:
        raise RuntimeError("--offset cannot be negative")
    if args.batch_size < 1:
        raise RuntimeError("--batch-size must be at least 1")
    if args.scale_import_max < 1:
        raise RuntimeError("--scale-import-max must be at least 1")
    if args.resume and args.reset_checkpoint:
        raise RuntimeError("--resume and --reset-checkpoint cannot be used together")
    if args.resume and args.offset is not None:
        raise RuntimeError("--resume requires a keyset checkpoint, not --offset")
    if args.missing_only and args.target != "neogiga":
        raise RuntimeError("--missing-only is supported only for the NeoGiga canonical target")
    settings = Settings.from_env(batch_size=args.batch_size)
    settings.ensure_dirs()
    logger = configure_logging(args.log_level, settings.output_dir / "jlcpcb_etl.jsonl")
    started = datetime.now(timezone.utc).isoformat()
    timer = perf_counter()
    checkpoint_store = CheckpointStore(settings.checkpoint_dir / "jlcpcb_import_checkpoint.json")
    if args.reset_checkpoint:
        checkpoint_store.reset()
        logger.info("checkpoint reset", extra={"event": "checkpoint_reset"})

    logger.info("starting ETL", extra={"event": "etl_start"})
    if args.target == "neogiga":
        resolved = _resolve_neogiga_connection(args, settings)
        logger.info(
            "NeoGiga database configuration",
            extra={"event": "database_config", "error_message": resolved.redacted, "connection_source": resolved.source},
        )
        if args.print_connection_source:
            print(resolved.source)
            return 0
        if args.connection_check:
            adapter = TargetAdapterRegistry.create_neogiga(
                resolved.dsn,
                source_checksum=None,
                dry_run=True,
                no_search_index=args.no_search_index,
                no_seo=args.no_seo,
            )
            if not adapter.connection_check():
                raise RuntimeError("NeoGiga PostgreSQL connection check failed")
            logger.info("NeoGiga PostgreSQL connectivity validated", extra={"event": "postgres_connectivity", "rows_loaded": 1})
            return 0
        if args.rollback_batch:
            adapter = TargetAdapterRegistry.create_neogiga(
                resolved.dsn,
                source_checksum=None,
                dry_run=not args.publish,
                no_search_index=args.no_search_index,
                no_seo=args.no_seo,
            )
            plan = adapter.rollback(args.rollback_batch, dry_run=not args.publish)
            (settings.output_dir / "rollback_plan.json").write_text(json.dumps(plan, indent=2, default=str), encoding="utf-8")
            logger.info("rollback plan written", extra={"event": "rollback_plan_written"})
            return 0
    else:
        logger.info(
            "database configuration",
            extra={"event": "database_config", "error_message": redact_database_url(settings.database_url)},
        )

    if args.download_only:
        result = _download_or_wrap(args, settings)
        logger.info(
            "download complete",
            extra={"event": "download_complete", "rows_read": result.size_bytes, "error_message": result.source_url},
        )
        return 0

    if args.sqlite_file:
        result = _download_or_wrap(args, settings)
    else:
        readme = fetch_readme()
        discovered = discover_sqlite_url(readme, settings.sqlite_url_override)
        logger.info("source URL discovered", extra={"event": "source_url_discovered", "error_message": discovered})
        if args.dry_run or args.inspect_only or args.validate_only:
            # Dry-runs still need a local SQLite file to inspect. The file is large, so users may
            # provide --sqlite-file when disk/network budget is tight.
            result = _download_or_wrap(args, settings)
        else:
            result = _download_or_wrap(args, settings)

    schema_path = settings.output_dir / "sqlite_schema_report.json"
    schema_report = inspect_sqlite_schema(result.path, schema_path)
    if args.inspect_only:
        logger.info("schema inspection complete", extra={"event": "schema_inspection_complete"})
        return 0

    mapping = _mapping_from_report(schema_report)
    after_source_id, source_offset, resume_checkpoint = _resolve_source_cursor(
        args,
        checkpoint_store,
        source_checksum=result.checksum,
        source_table=mapping.parts_table,
    )
    provenance = _source_provenance(result, mapping, started)
    category_mapper = CategoryMapper()

    pg_ok = False
    if args.target == "neogiga":
        adapter = TargetAdapterRegistry.create_neogiga(
            resolved.dsn,
            source_checksum=result.checksum,
            dry_run=True,
            no_search_index=args.no_search_index,
            no_seo=args.no_seo,
        )
        pg_ok = adapter.connection_check()
        if args.dry_run or args.validate_only:
            logger.info("NeoGiga PostgreSQL connectivity validated", extra={"event": "postgres_connectivity", "rows_loaded": int(pg_ok)})
    else:
        pg_ok = validate_connection(settings.database_url) if settings.database_url else False
    if args.target == "standalone" and settings.database_url and (args.dry_run or args.validate_only):
        logger.info("PostgreSQL connectivity validated", extra={"event": "postgres_connectivity", "rows_loaded": int(pg_ok)})
    elif args.target == "standalone" and not settings.database_url:
        logger.info("DATABASE_URL not configured; PostgreSQL validation skipped", extra={"event": "postgres_connectivity_skipped"})

    is_write = not args.dry_run and not args.validate_only
    processed: list[TransformedPart] = []
    validation_metrics: ValidationAccumulator | None = None
    skipped: Counter[str] = Counter()
    source_rows_read = 0
    last_source_id = after_source_id
    adapter_report: dict[str, Any] | None = None

    if args.target == "neogiga" and is_write:
        if args.offset is not None:
            raise RuntimeError("NeoGiga writes require --after-source-id or --resume; --offset is inspection-only")
        if args.missing_only and after_source_id is None:
            raise RuntimeError("--missing-only requires an explicit --after-source-id or --resume cursor")
        if not args.publish or not args.pilot or not args.yes:
            raise RuntimeError("Refusing NeoGiga canonical writes without --publish --pilot --yes")
        max_neogiga_rows = max(1000, args.scale_import_max) if args.scale_import else 1000
        if args.limit is None or args.limit > max_neogiga_rows:
            raise RuntimeError(f"NeoGiga writes are capped at --limit {max_neogiga_rows} for this execution")
        if not pg_ok:
            raise RuntimeError("NeoGiga PostgreSQL connectivity validation failed")

        mode = "neogiga_scale_hidden_pending" if args.scale_import else "neogiga_pilot"
        commit_row_limit = min(settings.batch_size, MAX_COMMIT_ROWS)
        run_id = str(uuid4())
        adapter = TargetAdapterRegistry.create_neogiga(
            resolved.dsn,
            source_checksum=result.checksum,
            dry_run=False,
            no_search_index=args.no_search_index,
            no_seo=args.no_seo,
            source_provenance=provenance,
            import_mode=mode,
        )
        if resume_checkpoint:
            adapter.validate_resume_checkpoint(
                str(resume_checkpoint.import_batch_id),
                result.checksum,
                str(resume_checkpoint.last_processed_key),
            )

        adapter_report = _new_adapter_run_report(
            run_id=run_id,
            mode=mode,
            source_checksum=result.checksum,
            source_provenance=provenance,
            after_source_id=after_source_id,
            commit_row_limit=commit_row_limit,
        )
        adapter_report["resumed_from_batch_id"] = (
            resume_checkpoint.import_batch_id if resume_checkpoint else None
        )
        _write_adapter_report(adapter_report, settings.output_dir)
        validation_metrics = ValidationAccumulator()
        chunk_parts: list[TransformedPart] = []
        chunk_errors: list[dict[str, Any]] = []
        chunk_rows_read = 0
        chunk_after_source_id = after_source_id
        chunk_last_source_id: str | None = None
        previous_source_id = after_source_id
        last_checkpoint: ImportCheckpoint | None = None

        def commit_chunk() -> None:
            nonlocal chunk_parts, chunk_errors, chunk_rows_read
            nonlocal chunk_after_source_id, chunk_last_source_id, last_checkpoint
            if chunk_rows_read == 0:
                return
            if chunk_errors:
                failed_ids = ", ".join(str(error.get("source_part_id")) for error in chunk_errors[:5])
                raise RuntimeError(
                    f"Refusing to commit a partially transformed NeoGiga chunk: "
                    f"{len(chunk_errors)} transform error(s); first source IDs: {failed_ids}"
                )
            batch_result = adapter.publish(
                chunk_parts,
                after_source_id=chunk_after_source_id,
                last_source_id=chunk_last_source_id,
                source_rows_read=chunk_rows_read,
                transform_errors=chunk_errors,
                run_id=run_id,
                resumed_from_batch_id=(
                    str(resume_checkpoint.import_batch_id) if resume_checkpoint else None
                ),
            )
            _record_committed_batch(adapter_report, batch_result)
            last_checkpoint = ImportCheckpoint(
                source_checksum=result.checksum,
                import_batch_id=batch_result.import_batch_id,
                source_table=mapping.parts_table,
                last_processed_key=batch_result.last_source_id,
                rows_read=adapter_report["rows_read"],
                rows_loaded=adapter_report["products_inserted"] + adapter_report["products_updated"],
                rows_skipped=adapter_report["skipped"],
                target="neogiga",
                run_id=run_id,
                source_file=result.path.name,
                source_url=result.source_url,
                committed_batch_ids=list(adapter_report["import_batch_ids"]),
                status="running",
            )
            evidence_errors: list[str] = []
            try:
                _write_adapter_report(adapter_report, settings.output_dir)
            except Exception as exc:
                evidence_errors.append(f"run report: {exc}")
            try:
                checkpoint_store.write(last_checkpoint)
            except Exception as exc:
                evidence_errors.append(f"checkpoint: {exc}")
            if evidence_errors:
                raise RuntimeError(
                    f"Batch {batch_result.import_batch_id} committed but local evidence persistence failed: "
                    + "; ".join(evidence_errors)
                )
            logger.info(
                "NeoGiga chunk committed",
                extra={
                    "event": "neogiga_chunk_committed",
                    "rows_read": batch_result.rows_read,
                    "rows_loaded": batch_result.products_inserted + batch_result.products_updated,
                    "rows_skipped": batch_result.skipped,
                    "import_batch_id": batch_result.import_batch_id,
                },
            )
            chunk_after_source_id = chunk_last_source_id
            chunk_parts = []
            chunk_errors = []
            chunk_rows_read = 0
            chunk_last_source_id = None

        def process_records(records: list[dict[str, object]]) -> None:
            nonlocal source_rows_read, last_source_id, previous_source_id
            nonlocal chunk_rows_read, chunk_last_source_id

            existing_source_ids = (
                adapter.existing_source_part_ids(_source_key(record, mapping) for record in records)
                if args.missing_only
                else set()
            )
            for record in records:
                source_id = _source_key(record, mapping)
                if previous_source_id is not None and source_id == previous_source_id:
                    raise RuntimeError(f"Duplicate ordered source ID {source_id}; refusing an unsafe keyset checkpoint")
                previous_source_id = source_id
                source_rows_read += 1
                chunk_last_source_id = source_id
                last_source_id = source_id
                adapter_report["source_rows_scanned"] += 1
                if source_id in existing_source_ids:
                    adapter_report["source_rows_already_linked"] += 1
                    continue
                chunk_rows_read += 1
                try:
                    part = transform_record(record, mapping, category_mapper)
                    chunk_parts.append(part)
                    validation_metrics.add(part)
                except Exception as exc:
                    reason = str(exc)
                    skipped[reason] += 1
                    chunk_errors.append(
                        {
                            "source_part_id": source_id,
                            "reason": reason,
                            "raw_record": record,
                        }
                    )
                if chunk_rows_read >= commit_row_limit:
                    commit_chunk()

        try:
            scan_buffer: list[dict[str, object]] = []
            for record in stream_source_rows(
                result.path,
                mapping,
                limit=args.limit,
                after_source_id=after_source_id,
            ):
                if not args.missing_only:
                    process_records([record])
                    continue
                scan_buffer.append(record)
                if len(scan_buffer) >= commit_row_limit:
                    process_records(scan_buffer)
                    scan_buffer = []
            if scan_buffer:
                process_records(scan_buffer)
            commit_chunk()
            adapter_report["status"] = "completed"
            adapter_report["finished_at"] = datetime.now(timezone.utc).isoformat()
            _write_adapter_report(adapter_report, settings.output_dir)
            if last_checkpoint:
                last_checkpoint.status = "completed"
                last_checkpoint.timestamp = ""
                checkpoint_store.write(last_checkpoint)
        except Exception as exc:
            adapter_report["status"] = "failed"
            adapter_report["failure"] = str(exc)
            adapter_report["attempted_last_source_id"] = last_source_id
            adapter_report["finished_at"] = datetime.now(timezone.utc).isoformat()
            try:
                _write_adapter_report(adapter_report, settings.output_dir)
            except Exception:
                pass
            committed = ", ".join(adapter_report["import_batch_ids"]) or "none"
            raise RuntimeError(
                f"NeoGiga import failed; committed batches are reported as [{committed}] and the last durable cursor is "
                f"{adapter_report.get('last_source_id')!r}: {exc}"
            ) from exc
    else:
        previous_source_id = after_source_id
        for record in stream_source_rows(
            result.path,
            mapping,
            limit=args.limit,
            offset=source_offset,
            after_source_id=after_source_id,
        ):
            source_id = _source_key(record, mapping)
            if previous_source_id is not None and source_id == previous_source_id:
                raise RuntimeError(f"Duplicate ordered source ID {source_id}; refusing an unsafe keyset checkpoint")
            previous_source_id = source_id
            source_rows_read += 1
            last_source_id = source_id
            try:
                processed.append(transform_record(record, mapping, category_mapper))
            except Exception as exc:
                skipped[str(exc)] += 1

        if is_write:
            if not args.yes:
                raise RuntimeError("Refusing catalog writes without --yes. Run --dry-run first, then import explicitly.")
            if not settings.database_url:
                raise RuntimeError("DATABASE_URL is required for import and must come from the environment")
            ensure_schema(settings.database_url)
            last_checkpoint = None
            for index in range(0, len(processed), settings.batch_size):
                batch = processed[index : index + settings.batch_size]
                counts = load_batch(settings.database_url, batch)
                last_checkpoint = ImportCheckpoint(
                    source_checksum=result.checksum,
                    import_batch_id=None,
                    source_table=mapping.parts_table,
                    last_processed_key=batch[-1].source_part_id if batch else None,
                    rows_read=index + len(batch),
                    rows_loaded=index + len(batch),
                    rows_skipped=sum(skipped.values()),
                    source_offset=source_offset,
                    target="standalone",
                    run_id=None,
                    source_file=result.path.name,
                    source_url=result.source_url,
                    status="running",
                )
                checkpoint_store.write(last_checkpoint)
                logger.info(
                    "batch loaded",
                    extra={
                        "event": "batch_loaded",
                        "batch_number": index // settings.batch_size + 1,
                        "rows_read": len(batch),
                        "rows_loaded": counts["parts"],
                        "rows_skipped": sum(skipped.values()),
                    },
                )
            if last_checkpoint:
                last_checkpoint.status = "completed"
                last_checkpoint.timestamp = ""
                checkpoint_store.write(last_checkpoint)
        else:
            logger.info(
                "dry-run no-write complete",
                extra={
                    "event": "dry_run_complete",
                    "rows_read": source_rows_read,
                    "rows_loaded": len(processed),
                    "rows_skipped": sum(skipped.values()),
                },
            )

    report = build_validation_report(
        source_url=result.source_url,
        source_checksum=result.checksum,
        schema_report=schema_report,
        processed=processed,
        skipped=skipped,
        started_at=started,
        accumulator=validation_metrics,
    )
    report["source_offset"] = source_offset
    report["source_end_offset"] = source_offset + source_rows_read if source_offset else None
    report["after_source_id"] = after_source_id
    report["last_source_id"] = last_source_id
    report["source_rows_read"] = source_rows_read
    if adapter_report:
        report["total_parts_inserted"] = adapter_report["products_inserted"]
        report["total_parts_updated"] = adapter_report["products_updated"]
        report["canonical_aliases_created"] = adapter_report["source_aliases_created"]
        report["canonical_aliases_updated"] = adapter_report["source_aliases_updated"]
        report["import_batch_ids"] = adapter_report["import_batch_ids"]
        report["source_rows_scanned"] = adapter_report["source_rows_scanned"]
        report["source_rows_already_linked"] = adapter_report["source_rows_already_linked"]
    report["total_runtime_seconds"] = round(perf_counter() - timer, 3)
    if report["total_runtime_seconds"]:
        report["rows_per_second"] = round(source_rows_read / report["total_runtime_seconds"], 2)
    write_validation_report(report, settings.output_dir)
    logger.info("validation report written", extra={"event": "validation_report_written", "duration_ms": int((perf_counter() - timer) * 1000)})
    return 0


def main() -> None:
    raise SystemExit(run())


if __name__ == "__main__":
    main()
