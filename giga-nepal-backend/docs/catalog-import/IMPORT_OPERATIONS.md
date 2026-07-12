# Import Operations

## Safe Defaults

All supplier imports and media downloads are disabled in `.env.example`. Imported canonical products receive `status=pending`, `approval_status=pending_review` where supported, and `visibility_status=hidden` where supported.

## Commands

```bash
php artisan catalog:supplier-audit adafruit
php artisan catalog:import adafruit --dry-run --limit=20
php artisan catalog:import waveshare --dry-run --limit=20
php artisan catalog:import okystar --dry-run --limit=20
php artisan catalog:status
php artisan catalog:stage-supplier-csv /absolute/path/normalized-supplier-quotation.csv --dry-run
php artisan catalog:stage-supplier-csv /absolute/path/normalized-supplier-quotation.csv
```

Dry runs generate reports under `storage/app/catalog/reports/{run-id}` and do not create product, supplier-product, or import-run records. A non-dry-run import remains blocked unless both configuration and database policy approval are enabled.

## Next Safe Production Step

1. Obtain and record supplier feed/API or written reuse permission.
2. Run `catalog:supplier-audit SUPPLIER` from a non-production environment.
3. Review robots and terms; mark only the relevant source `approved` through the protected admin API.
4. Enable only that supplier and run a 20-product non-production staging import.
5. Validate pending-review records, then approve individual products through existing admin workflow.

Do not deploy this code or enable an importer until the approved deployment workflow and source permissions exist.

## User-provided quotation documents

Use `catalog:stage-supplier-csv` only for a normalized supplier quotation CSV supplied to NeoGiga by an authorized operator. It is a document-staging operation, not supplier web crawling.

The command validates the required provenance columns, stores source values and raw input, creates hidden pending-review products when no verified manufacturer plus MPN identity is available, and writes a report in `storage/app/catalog/reports/{run-id}`. It does not set inventory, marketplace price overlays, product media, search documents, or public publication status. Re-running an unchanged CSV is idempotent.

Before a production run: take a production backup, apply only additive migrations, run `--dry-run`, review report counters and raw source records, and obtain an authorized approval decision for any pricing, content reuse, image, or publication action.
