<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Jobs\CustomerImport\ProcessCustomerImportJob;
use App\Services\CustomerImport\CustomerImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CustomerImportController extends Controller
{
    use ApiResponses;

    public function preview(Request $request, CustomerImportService $imports): JsonResponse
    {
        $maxKb = (int) config('customer_import.max_file_size_kb', 20480);
        $data = $request->validate([
            'file' => ['required', 'file', "max:{$maxKb}", 'extensions:xlsx,xls,csv,ods'],
            'profile' => ['nullable', 'string', 'max:190'],
            'sheet' => ['nullable', 'string', 'max:190'],
        ]);
        $token = Str::random(64);
        $file = $request->file('file');
        if (! in_array($file->getMimeType(), config('customer_import.allowed_mime_types', []), true)) {
            return $this->error('The uploaded file MIME type is not allowed.', 422);
        }
        $extension = strtolower($file->getClientOriginalExtension());
        $stored = $file->storeAs('customer-import-previews', $token.'.'.$extension, 'local');
        $path = Storage::disk('local')->path($stored);
        $profile = $data['profile'] ?? 'Customer Invoice Details';
        try {
            $preview = $imports->preview($path, $profile, $data['sheet'] ?? null);
        } catch (Throwable) {
            Storage::disk('local')->delete($stored);

            return $this->error('The uploaded file could not be read as the declared spreadsheet type.', 422);
        }
        Cache::put("customer-import-preview:{$token}", [
            'path' => $path,
            'profile' => $profile,
            'sheet' => $preview['worksheet'],
        ], now()->addHour());

        return $this->success(['preview_token' => $token, 'expires_in_seconds' => 3600, 'preview' => $preview]);
    }

    public function execute(Request $request): JsonResponse
    {
        $data = $request->validate([
            'preview_token' => ['required', 'string', 'size:64'],
            'country' => ['nullable', 'string', 'max:190'],
            'marketplace' => ['nullable', 'string', 'max:190'],
            'batch' => ['nullable', 'string', 'max:190'],
            'source' => ['nullable', 'string', 'max:190'],
            'only_valid' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
        ]);
        $preview = Cache::pull("customer-import-preview:{$data['preview_token']}");
        if (! is_array($preview) || ! is_file($preview['path'] ?? '')) {
            return $this->error('The secure import preview has expired. Upload the file again.', 410);
        }
        ProcessCustomerImportJob::dispatch($preview['path'], [
            'profile' => $preview['profile'],
            'sheet' => $preview['sheet'],
            'country' => $data['country'] ?? null,
            'marketplace' => $data['marketplace'] ?? null,
            'batch' => $data['batch'] ?? null,
            'source' => $data['source'] ?? null,
            'only_valid' => (bool) ($data['only_valid'] ?? false),
            'update_existing' => (bool) ($data['update_existing'] ?? false),
            'no_marketing_consent' => true,
        ])->onQueue('imports');

        return $this->success(['status' => 'queued', 'queue' => 'imports', 'marketing_consent' => 'unknown'], 202);
    }

    public function status(int $import): JsonResponse
    {
        $record = DB::table('customer_imports')->find($import);
        if (! $record) {
            return $this->error('Customer import not found.', 404);
        }

        return $this->success([
            'id' => $record->id,
            'uuid' => $record->uuid,
            'file_name' => $record->original_file_name,
            'worksheet' => $record->worksheet,
            'source_name' => $record->source_name,
            'source_url' => $record->source_url,
            'status' => $record->status,
            'counts' => collect((array) $record)->only([
                'total_rows', 'valid_rows', 'imported_rows', 'updated_rows', 'skipped_rows', 'duplicate_rows',
                'warning_rows', 'error_rows', 'unresolved_countries', 'unresolved_companies',
            ]),
            'consent_state' => $record->consent_state,
            'started_at' => $record->started_at,
            'completed_at' => $record->completed_at,
        ]);
    }
}
