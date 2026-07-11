"""Command line interface for the NeoGiga JLCPCB ETL."""

from __future__ import annotations

import argparse
import json
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path
from time import perf_counter

from .adapters import TargetAdapterRegistry
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
from .validation import build_validation_report, write_validation_report


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Import open JLCPCB/LCSC parts database into PostgreSQL")
    parser.add_argument("--dry-run", action="store_true", help="Inspect and transform samples without database writes")
    parser.add_argument("--limit", type=int, default=None, help="Maximum rows to process")
    parser.add_argument("--batch-size", type=int, default=5000, help="Load batch size")
    parser.add_argument("--resume", action="store_true", help="Resume only if source checksum matches checkpoint")
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


def _write_adapter_report(result, output_dir: Path, *, mode: str) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)
    payload = result.__dict__.copy()
    payload["mode"] = mode
    (output_dir / "canonical_adapter_report.json").write_text(json.dumps(payload, indent=2, default=str), encoding="utf-8")
    lines = [
        "# NeoGiga Canonical Adapter Report",
        "",
        f"- Mode: {mode}",
        f"- Import batch: {result.import_batch_id}",
        f"- Rows read: {result.rows_read}",
        f"- Products inserted: {result.products_inserted}",
        f"- Products updated: {result.products_updated}",
        f"- Brands inserted: {result.brands_inserted}",
        f"- Categories inserted: {result.categories_inserted}",
        f"- Source links created: {result.source_links_created}",
        f"- Source links updated: {result.source_links_updated}",
        f"- Specifications created: {result.specifications_created}",
        f"- Specifications updated: {result.specifications_updated}",
        f"- Offers created: {result.offers_created}",
        f"- Offers updated: {result.offers_updated}",
        f"- Skipped: {result.skipped}",
        "",
        "Full machine-readable report is in `canonical_adapter_report.json`.",
    ]
    (output_dir / "canonical_adapter_report.md").write_text("\n".join(lines) + "\n", encoding="utf-8")


def run(argv: list[str] | None = None) -> int:
    args = build_parser().parse_args(argv)
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

    if args.download_only and not args.sqlite_file:
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

    if args.resume and not checkpoint_store.can_resume(result.checksum):
        raise RuntimeError("Checkpoint checksum does not match current source; refusing unsafe resume")

    schema_path = settings.output_dir / "sqlite_schema_report.json"
    schema_report = inspect_sqlite_schema(result.path, schema_path)
    if args.inspect_only:
        logger.info("schema inspection complete", extra={"event": "schema_inspection_complete"})
        return 0

    mapping = _mapping_from_report(schema_report)
    category_mapper = CategoryMapper()
    processed: list[TransformedPart] = []
    skipped: Counter[str] = Counter()
    rows_read = 0

    for record in stream_source_rows(result.path, mapping, limit=args.limit):
        rows_read += 1
        try:
            processed.append(transform_record(record, mapping, category_mapper))
        except Exception as exc:
            skipped[str(exc)] += 1
        if len(processed) >= (args.limit or len(processed) + 1):
            break

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

    if not args.dry_run and not args.validate_only:
        if args.target == "neogiga":
            if not args.publish or not args.pilot:
                raise RuntimeError("Refusing NeoGiga canonical writes without --publish --pilot")
            max_neogiga_rows = max(1000, args.scale_import_max) if args.scale_import else 1000
            if args.limit is None or args.limit > max_neogiga_rows:
                raise RuntimeError(f"NeoGiga writes are capped at --limit {max_neogiga_rows} for this execution")
            adapter = TargetAdapterRegistry.create_neogiga(
                resolved.dsn,
                source_checksum=result.checksum,
                dry_run=False,
                no_search_index=args.no_search_index,
                no_seo=args.no_seo,
            )
            adapter_result = adapter.publish(processed)
            _write_adapter_report(adapter_result, settings.output_dir, mode="neogiga_scale_hidden_pending" if args.scale_import else "neogiga_pilot")
            if adapter_result.skipped:
                raise RuntimeError(f"NeoGiga pilot stopped with {adapter_result.skipped} adapter errors")
            checkpoint_store.write(
                ImportCheckpoint(
                    source_checksum=result.checksum,
                    import_batch_id=adapter_result.import_batch_id,
                    source_table=mapping.parts_table,
                    last_processed_key=processed[-1].source_part_id if processed else None,
                    rows_read=rows_read,
                    rows_loaded=adapter_result.products_inserted + adapter_result.products_updated,
                    rows_skipped=sum(skipped.values()) + adapter_result.skipped,
                )
            )
            logger.info(
                "NeoGiga pilot batch loaded",
                extra={
                    "event": "neogiga_pilot_loaded",
                    "rows_read": rows_read,
                    "rows_loaded": adapter_result.products_inserted + adapter_result.products_updated,
                    "rows_skipped": sum(skipped.values()) + adapter_result.skipped,
                    "import_batch_id": adapter_result.import_batch_id,
                },
            )
        else:
            if not args.yes:
                raise RuntimeError("Refusing catalog writes without --yes. Run --dry-run first, then import explicitly.")
            if not settings.database_url:
                raise RuntimeError("DATABASE_URL is required for import and must come from the environment")
            ensure_schema(settings.database_url)
            for index in range(0, len(processed), settings.batch_size):
                batch = processed[index : index + settings.batch_size]
                counts = load_batch(settings.database_url, batch)
                checkpoint_store.write(
                    ImportCheckpoint(
                        source_checksum=result.checksum,
                        import_batch_id=None,
                        source_table=mapping.parts_table,
                        last_processed_key=batch[-1].source_part_id if batch else None,
                        rows_read=rows_read,
                        rows_loaded=index + len(batch),
                        rows_skipped=sum(skipped.values()),
                    )
                )
                logger.info(
                    "batch loaded",
                    extra={
                        "event": "batch_loaded",
                        "batch_number": index // settings.batch_size + 1,
                        "rows_read": rows_read,
                        "rows_loaded": counts["parts"],
                        "rows_skipped": sum(skipped.values()),
                    },
                )
    else:
        logger.info(
            "dry-run no-write complete",
            extra={"event": "dry_run_complete", "rows_read": rows_read, "rows_loaded": len(processed), "rows_skipped": sum(skipped.values())},
        )

    report = build_validation_report(
        source_url=result.source_url,
        source_checksum=result.checksum,
        schema_report=schema_report,
        processed=processed,
        skipped=skipped,
        started_at=started,
    )
    report["total_runtime_seconds"] = round(perf_counter() - timer, 3)
    if report["total_runtime_seconds"]:
        report["rows_per_second"] = round(rows_read / report["total_runtime_seconds"], 2)
    write_validation_report(report, settings.output_dir)
    logger.info("validation report written", extra={"event": "validation_report_written", "duration_ms": int((perf_counter() - timer) * 1000)})
    return 0


def main() -> None:
    raise SystemExit(run())


if __name__ == "__main__":
    main()
