# NeoGiga Production Catalog Import Pipeline

This pipeline imports only official/licensed product feeds into the NeoGiga canonical catalog.

It refuses to import when:

- source is not marked official
- redistribution is not explicitly allowed
- license is unknown
- feed path is missing/unavailable
- MPN or manufacturer is missing
- category mapping fails
- duplicate rate exceeds threshold
- image rights are unknown

## Feed Format

CSV, JSON, and JSONL feeds are supported. Configure source feeds in:

`tools/catalog_import_pipeline/source_registry.yaml`

Each feed must map fields for manufacturer, MPN, source ID, category, specs, datasheet, provenance and optional image metadata.

## Dry Run

```bash
python -m tools.catalog_import_pipeline.cli --list-sources
python -m tools.catalog_import_pipeline.cli --source licensed_distributor_feed --limit 1000
```

## Production Apply

```bash
python -m tools.catalog_import_pipeline.cli --source licensed_distributor_feed --target 20000 --apply --laravel-path /home/neogiga/laravel/current
```

Do not use `--apply` until validation passes and a production database backup exists.

## Reports

Reports are written to:

`tools/catalog_import_pipeline/output/PRODUCTION_CATALOG_IMPORT_REPORT.md`

