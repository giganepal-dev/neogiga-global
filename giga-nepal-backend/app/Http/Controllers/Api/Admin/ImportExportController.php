<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Bulk import/export (Blueprint §26 stock sync, FR-42).
 *
 * Routes are gated by the admin.token middleware (SEC-02). Implementation
 * is deliberately deferred: imports must run as queued jobs with dry-run
 * diffing and a secure file-upload pipeline (SEC-14), and the imports/
 * import_rows/export_jobs migrations are still empty shells (DB-02).
 */
class ImportExportController extends Controller
{
    use ApiResponses;

    public function dryRun(): JsonResponse
    {
        return $this->notImplemented('Import dry-run');
    }

    public function execute(): JsonResponse
    {
        return $this->notImplemented('Import execution');
    }

    public function show(int $import): JsonResponse
    {
        return $this->notImplemented('Import status');
    }

    public function errors(int $import): JsonResponse
    {
        return $this->notImplemented('Import error report');
    }

    public function createExport(): JsonResponse
    {
        return $this->notImplemented('Export jobs');
    }
}
