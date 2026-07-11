# NeoGiga Production Catalog Pipeline Plan

## Status

Implemented a guarded import pipeline for official/licensed manufacturer and distributor feeds. No new external products were imported because no configured feed currently proves redistribution rights.

## Source Requirements

Every source must provide:

- Official source URL or licensed distributor feed URL
- License name and license URL/contract reference
- Explicit redistribution permission
- Explicit image redistribution permission before images are imported
- Stable source part ID
- Manufacturer and MPN
- Category/subcategory mapping
- Provenance metadata

## Current Blocker

The local `Raspberry Pi.xlsx` file was found, but it has no source manifest proving license and redistribution rights. The pipeline will not import it until the source registry is updated with verified rights.

## Next Phase

1. Add official feed/API credentials or licensed CSV/JSONL exports.
2. Fill `source_registry.yaml` per source.
3. Run dry-run validation with `--target 20000`.
4. Review `PRODUCTION_CATALOG_IMPORT_REPORT.md`.
5. Take production DB backup.
6. Run `--apply`.
7. Rebuild search/facets and SEO queues.
