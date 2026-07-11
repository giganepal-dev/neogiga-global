from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from tools.jlcpcb_etl.connection_resolver import resolve_connection

from .canonical_writer import CanonicalCatalogWriter
from .feed_reader import read_feed
from .marketplaces import load_marketplaces
from .source_registry import load_source_manifests, manifest_to_safe_report
from .validator import CatalogValidationError, validate_products


DEFAULT_REGISTRY = Path(__file__).with_name("source_registry.yaml")
DEFAULT_MARKETPLACES = Path(__file__).with_name("marketplaces.yaml")
DEFAULT_OUTPUT = Path(__file__).with_name("output")


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="NeoGiga licensed production catalog import pipeline")
    parser.add_argument("--registry", default=str(DEFAULT_REGISTRY), help="YAML source registry")
    parser.add_argument("--marketplaces", default=str(DEFAULT_MARKETPLACES), help="YAML marketplace localization config")
    parser.add_argument("--source", action="append", help="Source code to import; repeatable. Defaults to all configured feed sources.")
    parser.add_argument("--limit", type=int, default=0, help="Maximum rows per source, 0 means all")
    parser.add_argument("--target", type=int, default=20000, help="Target valid products for production import")
    parser.add_argument("--duplicate-threshold", type=float, default=0.05, help="Maximum duplicate rate before stop")
    parser.add_argument("--publish-threshold", type=float, default=0.90, help="Quality threshold for publication-ready status")
    parser.add_argument("--laravel-path", default=".", help="Laravel base path for .env DB resolution")
    parser.add_argument("--database-url", default=None, help="PostgreSQL DATABASE_URL override")
    parser.add_argument("--dsn", default=None, help="Explicit PostgreSQL DSN override")
    parser.add_argument("--apply", action="store_true", help="Write to canonical catalog. Default is dry-run.")
    parser.add_argument("--list-sources", action="store_true", help="Print configured source safety summary")
    parser.add_argument("--output-dir", default=str(DEFAULT_OUTPUT), help="Report output directory")
    return parser


def main(argv: list[str] | None = None) -> int:
    args = build_parser().parse_args(argv)
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    registry = load_source_manifests(args.registry)
    if args.list_sources:
        print(json.dumps([manifest_to_safe_report(item) for item in registry.values()], indent=2))
        return 0

    selected_codes = args.source or [code for code, manifest in registry.items() if manifest.feed_path]
    if not selected_codes:
        report = _empty_blocked_report(registry)
        _write_reports(output_dir, report)
        print(report["stop_reason"])
        return 2

    marketplaces = load_marketplaces(args.marketplaces)
    all_products = []
    validation_reports: dict[str, Any] = {}
    blocked: list[dict[str, Any]] = []
    for code in selected_codes:
        manifest = registry.get(code)
        if not manifest:
            blocked.append({"source": code, "reason": "source code not found in registry"})
            continue
        try:
            products = read_feed(manifest, limit=args.limit)
            validation = validate_products(manifest, products, duplicate_threshold=args.duplicate_threshold)
            validation_reports[code] = validation.to_dict()
            all_products.extend((manifest, product) for product in products)
        except CatalogValidationError as exc:
            validation_reports[code] = exc.report.to_dict()
            blocked.append({"source": code, "reason": exc.report.stop_reason})
        except Exception as exc:
            blocked.append({"source": code, "reason": str(exc)})

    if blocked:
        report = _report("blocked", selected_codes, validation_reports, blocked, [], None)
        _write_reports(output_dir, report)
        print(report["stop_reason"])
        return 2

    if len(all_products) < args.target:
        report = _report(
            "blocked",
            selected_codes,
            validation_reports,
            [{"source": "all", "reason": f"valid products {len(all_products)} below target {args.target}"}],
            [],
            None,
        )
        _write_reports(output_dir, report)
        print(report["stop_reason"])
        return 2

    resolved = None
    if args.apply:
        resolved = resolve_connection(database_url=args.database_url, laravel_base_path=Path(args.laravel_path), cli_dsn=args.dsn)

    import_reports = []
    for manifest in {item[0].code: item[0] for item in all_products}.values():
        source_products = [product for source, product in all_products if source.code == manifest.code]
        writer = CanonicalCatalogWriter(
            resolved.dsn if resolved else "",
            source=manifest,
            marketplaces=marketplaces,
            dry_run=not args.apply,
            publish_threshold=args.publish_threshold,
        )
        import_reports.append({"source": manifest.code, **writer.import_products(source_products).to_report()})

    report = _report("applied" if args.apply else "dry_run", selected_codes, validation_reports, [], import_reports, resolved.redacted if resolved else None)
    _write_reports(output_dir, report)
    print(json.dumps({"status": report["status"], "products_considered": report["totals"]["products_considered"], "output_dir": str(output_dir)}, indent=2))
    return 0


def _empty_blocked_report(registry: dict[str, Any]) -> dict[str, Any]:
    return {
        "status": "blocked",
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "stop_reason": "No licensed feed_path is configured. Add official/licensed source feeds to source_registry.yaml.",
        "configured_sources": [manifest_to_safe_report(item) for item in registry.values()],
        "totals": {"products_considered": 0},
        "rollback_plan": "No data was written.",
    }


def _report(status: str, sources: list[str], validation: dict[str, Any], blocked: list[dict[str, Any]], imports: list[dict[str, Any]], dsn_source: str | None) -> dict[str, Any]:
    products_considered = sum(int(item.get("rows_read", 0)) for item in imports)
    imported = sum(int(item.get("products_inserted", 0)) for item in imports)
    updated = sum(int(item.get("products_updated", 0)) for item in imports)
    published = sum(int(item.get("products_published", 0)) for item in imports)
    pending = sum(int(item.get("products_pending_review", 0)) for item in imports)
    images_imported = sum(int(item.get("images_imported", 0)) for item in imports)
    images_skipped = sum(int(item.get("images_skipped", 0)) for item in imports)
    seo_pages = sum(int(item.get("seo_pages_generated", 0)) for item in imports)
    return {
        "status": status,
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "sources": sources,
        "stop_reason": "; ".join(f"{row['source']}: {row['reason']}" for row in blocked) if blocked else None,
        "validation": validation,
        "imports": imports,
        "database": dsn_source,
        "totals": {
            "products_considered": products_considered,
            "products_imported": imported,
            "products_updated": updated,
            "products_published": published,
            "products_pending_review": pending,
            "images_imported": images_imported,
            "images_skipped": images_skipped,
            "seo_pages_generated": seo_pages,
        },
        "rollback_plan": "Use catalog_import_batches.batch_id from the import report to delete source links/offers and revert product visibility for the batch. Restore the pre-import database backup if canonical product mutations must be reversed.",
    }


def _write_reports(output_dir: Path, report: dict[str, Any]) -> None:
    (output_dir / "PRODUCTION_CATALOG_IMPORT_REPORT.json").write_text(json.dumps(report, indent=2, default=str), encoding="utf-8")
    lines = [
        "# Production Catalog Import Report",
        "",
        f"- Status: {report['status']}",
        f"- Generated: {report['generated_at']}",
        f"- Stop reason: {report.get('stop_reason') or 'none'}",
        f"- Products considered: {report.get('totals', {}).get('products_considered', 0)}",
        f"- Products imported: {report.get('totals', {}).get('products_imported', 0)}",
        f"- Products updated: {report.get('totals', {}).get('products_updated', 0)}",
        f"- Products published: {report.get('totals', {}).get('products_published', 0)}",
        f"- Products pending review: {report.get('totals', {}).get('products_pending_review', 0)}",
        f"- Images imported: {report.get('totals', {}).get('images_imported', 0)}",
        f"- Images skipped: {report.get('totals', {}).get('images_skipped', 0)}",
        f"- SEO pages generated: {report.get('totals', {}).get('seo_pages_generated', 0)}",
        "",
        "## Rollback Plan",
        "",
        report.get("rollback_plan", "No data was written."),
    ]
    (output_dir / "PRODUCTION_CATALOG_IMPORT_REPORT.md").write_text("\n".join(lines) + "\n", encoding="utf-8")


if __name__ == "__main__":
    raise SystemExit(main())

