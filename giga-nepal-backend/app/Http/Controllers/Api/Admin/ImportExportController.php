<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bulk import/export (Blueprint §26 stock sync, FR-42).
 *
 * Routes are gated by the admin.token middleware (SEC-02). Supports CSV
 * and Excel imports with dry-run diffing, validation, and execution.
 */
class ImportExportController extends Controller
{
    use ApiResponses;

    public function dryRun(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'entity_type' => ['required', 'string', 'in:products,orders,customers,inventory,suppliers'],
            'column_mapping' => ['sometimes', 'array'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = $this->parseFile($file, $extension);

        if ($rows === []) {
            return $this->error('File is empty or could not be parsed.', 422);
        }

        $validRows = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowErrors = $this->validateRow($row, $validated['entity_type'], $index + 1);
            if ($rowErrors === []) {
                $validRows[] = $row;
            } else {
                $errors = array_merge($errors, $rowErrors);
            }
        }

        $importId = DB::table('imports')->insertGetId([
            'entity_type' => $validated['entity_type'],
            'file_path' => $file->store('imports'),
            'column_mapping' => json_encode($validated['column_mapping'] ?? []),
            'total_rows' => count($rows),
            'valid_rows' => count($validRows),
            'error_rows' => count($errors),
            'status' => 'dry_run',
            'dry_run_results' => json_encode([
                'preview' => array_slice($validRows, 0, 10),
                'errors' => array_slice($errors, 0, 50),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success([
            'import_id' => $importId,
            'total_rows' => count($rows),
            'valid_rows' => count($validRows),
            'error_rows' => count($errors),
            'preview' => array_slice($validRows, 0, 10),
            'errors' => array_slice($errors, 0, 50),
            'can_execute' => count($errors) === 0 || count($validRows) > 0,
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_id' => ['required', 'integer', 'exists:imports,id'],
        ]);

        $import = DB::table('imports')->where('id', $validated['import_id'])->first();

        if ($import->status !== 'dry_run') {
            return $this->error('Import must be in dry_run status to execute.', 422);
        }

        $filePath = $import->file_path;
        if (! Storage::exists($filePath)) {
            return $this->error('Import file not found.', 404);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $file = Storage::path($filePath);
        $rows = $this->parseFileFromPath($file, $extension);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            try {
                $result = $this->importRow($row, $import->entity_type);
                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable) {
                $skipped++;
            }
        }

        DB::table('imports')->where('id', $validated['import_id'])->update([
            'status' => 'completed',
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success([
            'import_id' => $validated['import_id'],
            'status' => 'completed',
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }

    public function show(int $import): JsonResponse
    {
        $record = DB::table('imports')->where('id', $import)->first();

        if (! $record) {
            return $this->error('Import not found.', 404);
        }

        return $this->success($record);
    }

    public function errors(int $import): JsonResponse
    {
        $record = DB::table('imports')->where('id', $import)->first();

        if (! $record) {
            return $this->error('Import not found.', 404);
        }

        $dryResults = json_decode($record->dry_run_results ?? '{}', true);
        $errors = $dryResults['errors'] ?? [];

        return $this->success([
            'import_id' => $import,
            'entity_type' => $record->entity_type,
            'total_errors' => count($errors),
            'errors' => $errors,
        ]);
    }

    public function createExport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'in:products,orders,customers,inventory,suppliers'],
            'format' => ['sometimes', 'string', 'in:csv,xlsx'],
            'filters' => ['sometimes', 'array'],
        ]);

        $entityType = $validated['entity_type'];
        $format = $validated['format'] ?? 'csv';

        $rows = $this->exportEntity($entityType, $validated['filters'] ?? []);

        $fileName = 'export_'.$entityType.'_'.now()->format('YmdHis').'.'.$format;
        $filePath = 'exports/'.$fileName;

        if ($format === 'csv') {
            $this->writeCsv($rows, $filePath);
        } else {
            $this->writeExcel($rows, $filePath);
        }

        $downloadUrl = Storage::disk('public')->url($filePath);

        return $this->success([
            'export_id' => strtoupper(Str::random(8)),
            'entity_type' => $entityType,
            'format' => $format,
            'total_rows' => count($rows),
            'file_path' => $filePath,
            'download_url' => $downloadUrl,
            'expires_at' => now()->addHours(24)->toDateTimeString(),
        ], 201);
    }

    private function parseFile($file, string $extension): array
    {
        if ($extension === 'csv') {
            return $this->parseCsvFile($file);
        }

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcelFile($file);
        }

        return [];
    }

    private function parseFileFromPath(string $path, string $extension): array
    {
        $file = new \SplFileObject($path);

        if ($extension === 'csv') {
            $file->setFlags(
                \SplFileObject::READ_CSV |
                \SplFileObject::SKIP_EMPTY |
                \SplFileObject::READ_AHEAD |
                \SplFileObject::DROP_NEW_LINE
            );

            $rows = [];
            $headers = null;
            foreach ($file as $index => $row) {
                if ($index === 0) {
                    $headers = $row;
                    continue;
                }
                if ($headers && is_array($row)) {
                    $rows[] = array_combine($headers, $row);
                }
            }

            return $rows;
        }

        return [];
    }

    private function parseCsvFile($file): array
    {
        $content = $file->get();
        $lines = array_filter(explode("\n", $content));

        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $rows = [];

        foreach ($lines as $line) {
            $values = str_getcsv($line);
            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }

    private function parseExcelFile($file): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (count($data) < 2) {
                return [];
            }

            $headers = $data[0];
            $rows = [];

            for ($i = 1; $i < count($data); $i++) {
                $rows[] = array_combine($headers, $data[$i]);
            }

            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }

    private function validateRow(array $row, string $entityType, int $rowNumber): array
    {
        $errors = [];

        switch ($entityType) {
            case 'products':
                if (empty($row['name'])) {
                    $errors[] = ['row' => $rowNumber, 'field' => 'name', 'error' => 'Name is required'];
                }
                if (empty($row['sku'])) {
                    $errors[] = ['row' => $rowNumber, 'field' => 'sku', 'error' => 'SKU is required'];
                }
                break;

            case 'orders':
                if (empty($row['order_number'])) {
                    $errors[] = ['row' => $rowNumber, 'field' => 'order_number', 'error' => 'Order number is required'];
                }
                break;

            case 'customers':
                if (empty($row['email'])) {
                    $errors[] = ['row' => $rowNumber, 'field' => 'email', 'error' => 'Email is required'];
                }
                break;

            case 'inventory':
                if (empty($row['product_id'])) {
                    $errors[] = ['row' => $rowNumber, 'field' => 'product_id', 'error' => 'Product ID is required'];
                }
                if (empty($row['warehouse_id'])) {
                    $errors[] = ['row' => $rowNumber, 'field' => 'warehouse_id', 'error' => 'Warehouse ID is required'];
                }
                break;

            case 'suppliers':
                if (empty($row['name'])) {
                    $errors[] = ['row' => $rowNumber, 'field' => 'name', 'error' => 'Supplier name is required'];
                }
                break;
        }

        return $errors;
    }

    private function importRow(array $row, string $entityType): string
    {
        switch ($entityType) {
            case 'products':
                $existing = DB::table('products')->where('sku', $row['sku'] ?? '')->first();
                if ($existing) {
                    DB::table('products')->where('id', $existing->id)->update([
                        'name' => $row['name'] ?? $existing->name,
                        'updated_at' => now(),
                    ]);
                    return 'updated';
                }
                DB::table('products')->insert([
                    'name' => $row['name'],
                    'slug' => Str::slug($row['name']),
                    'sku' => $row['sku'] ?? Str::random(10),
                    'base_price' => $row['base_price'] ?? 0,
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 'created';

            case 'orders':
                $existing = DB::table('orders')->where('order_number', $row['order_number'] ?? '')->first();
                if ($existing) {
                    return 'skipped';
                }
                DB::table('orders')->insert([
                    'order_number' => $row['order_number'],
                    'status' => $row['status'] ?? 'pending',
                    'grand_total' => $row['grand_total'] ?? 0,
                    'currency_code' => $row['currency_code'] ?? 'USD',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 'created';

            default:
                return 'skipped';
        }
    }

    private function exportEntity(string $entityType, array $filters): array
    {
        switch ($entityType) {
            case 'products':
                return DB::table('products')
                    ->select('id', 'name', 'slug', 'sku', 'base_price', 'sale_price', 'status', 'created_at')
                    ->orderBy('id')
                    ->get()
                    ->toArray();

            case 'orders':
                return DB::table('orders')
                    ->select('id', 'order_number', 'status', 'grand_total', 'currency_code', 'payment_method', 'created_at')
                    ->orderBy('id')
                    ->get()
                    ->toArray();

            case 'customers':
                return DB::table('users')
                    ->select('id', 'name', 'email', 'created_at')
                    ->orderBy('id')
                    ->get()
                    ->toArray();

            case 'inventory':
                return DB::table('inventory_stocks')
                    ->select('id', 'product_id', 'warehouse_id', 'marketplace_id', 'quantity_available', 'quantity_reserved')
                    ->orderBy('id')
                    ->get()
                    ->toArray();

            case 'suppliers':
                return DB::table('suppliers')
                    ->select('id', 'name', 'email', 'phone', 'status', 'created_at')
                    ->orderBy('id')
                    ->get()
                    ->toArray();

            default:
                return [];
        }
    }

    private function writeCsv(array $rows, string $filePath): void
    {
        $handle = fopen(Storage::path($filePath), 'w');
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                fputcsv($handle, array_keys((array) $row));
            }
            fputcsv($handle, array_values((array) $row));
        }
        fclose($handle);
    }

    private function writeExcel(array $rows, string $filePath): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($rows !== []) {
            $firstRow = (array) $rows[0];
            $sheet->fromArray(array_keys($firstRow), null, 'A1');

            $rowNum = 2;
            foreach ($rows as $row) {
                $sheet->fromArray(array_values((array) $row), null, 'A'.$rowNum);
                $rowNum++;
            }
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save(Storage::path($filePath));
    }
}
