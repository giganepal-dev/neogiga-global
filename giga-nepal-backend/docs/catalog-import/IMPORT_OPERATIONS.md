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
```

Dry runs generate reports under `storage/app/catalog/reports/{run-id}` and do not create product, supplier-product, or import-run records. A non-dry-run import remains blocked unless both configuration and database policy approval are enabled.

## Next Safe Production Step

1. Obtain and record supplier feed/API or written reuse permission.
2. Run `catalog:supplier-audit SUPPLIER` from a non-production environment.
3. Review robots and terms; mark only the relevant source `approved` through the protected admin API.
4. Enable only that supplier and run a 20-product non-production staging import.
5. Validate pending-review records, then approve individual products through existing admin workflow.

Do not deploy this code or enable an importer until the approved deployment workflow and source permissions exist.
