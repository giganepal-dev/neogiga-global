<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CustomerImport\ProcessCustomerImportJob;
use App\Services\CustomerImport\CustomerImportNormalizer;
use App\Services\CustomerImport\CustomerImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CustomerImportController extends Controller
{
    public function index(Request $request): View
    {
        return $this->viewData($request);
    }

    public function preview(Request $request, CustomerImportService $imports): View
    {
        $maxKb = (int) config('customer_import.max_file_size_kb', 20480);
        $data = $request->validate([
            'file' => ['required', 'file', "max:{$maxKb}", 'extensions:xlsx,xls,csv,ods'],
            'profile' => ['required', 'string', 'max:190'],
            'sheet' => ['nullable', 'string', 'max:190'],
        ]);
        $token = Str::random(64);
        $file = $request->file('file');
        abort_unless(in_array($file->getMimeType(), config('customer_import.allowed_mime_types', []), true), 422, 'The uploaded file MIME type is not allowed.');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('customer-import-previews', $token.'.'.$extension, 'local');
        $absolutePath = Storage::disk('local')->path($path);
        try {
            $preview = $imports->preview($absolutePath, $data['profile'], $data['sheet'] ?? null);
        } catch (Throwable) {
            Storage::disk('local')->delete($path);
            throw ValidationException::withMessages(['file' => 'The uploaded file could not be read as the declared spreadsheet type.']);
        }
        Cache::put("customer-import-preview:{$token}", [
            'path' => $absolutePath,
            'original_name' => $file->getClientOriginalName(),
            'profile' => $data['profile'],
            'sheet' => $preview['worksheet'],
            'uploaded_by' => $request->user()->id,
        ], now()->addHour());

        return $this->viewData($request, compact('preview', 'token'));
    }

    public function execute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preview_token' => ['required', 'string', 'size:64'],
            'sheet' => ['nullable', 'string', 'max:190'],
            'source' => ['nullable', 'string', 'max:190'],
            'marketplace' => ['nullable', 'string', 'max:190'],
            'country' => ['nullable', 'string', 'max:190'],
            'batch' => ['nullable', 'string', 'max:190'],
            'only_valid' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
        ]);
        $preview = Cache::pull("customer-import-preview:{$data['preview_token']}");
        abort_unless(is_array($preview) && is_file($preview['path'] ?? ''), 410, 'The secure import preview has expired. Upload the file again.');
        abort_unless((int) ($preview['uploaded_by'] ?? 0) === (int) $request->user()->id, 403, 'This import preview belongs to a different administrator.');

        ProcessCustomerImportJob::dispatch($preview['path'], [
            'profile' => $preview['profile'],
            'sheet' => $data['sheet'] ?: $preview['sheet'],
            'source' => $data['source'] ?? null,
            'marketplace' => $data['marketplace'] ?? null,
            'country' => $data['country'] ?? null,
            'batch' => $data['batch'] ?? null,
            'only_valid' => (bool) ($data['only_valid'] ?? false),
            'update_existing' => (bool) ($data['update_existing'] ?? false),
            'no_marketing_consent' => true,
            'uploaded_by' => $request->user()->id,
        ])->onQueue('imports');

        return redirect('/admin/marketing/customer-imports')->with('status', 'Customer import queued. Promotional consent remains unknown and disabled.');
    }

    public function show(int $import): View
    {
        $record = DB::table('customer_imports')->find($import);
        abort_unless($record, 404);

        return view('admin.marketing.customer-import-detail', [
            'import' => $record,
            'rows' => DB::table('customer_import_rows')->where('customer_import_id', $import)->orderBy('row_number')->paginate(50),
            'errors' => DB::table('customer_import_errors')->where('customer_import_id', $import)->orderBy('row_number')->limit(250)->get(),
        ]);
    }

    public function errors(int $import, CustomerImportNormalizer $normalizer): StreamedResponse
    {
        abort_unless(DB::table('customer_imports')->where('id', $import)->exists(), 404);

        return response()->streamDownload(function () use ($import, $normalizer) {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['row', 'field', 'code', 'severity', 'message', 'resolved']);
            DB::table('customer_import_errors')->where('customer_import_id', $import)->orderBy('id')->chunkById(500, function ($errors) use ($stream, $normalizer) {
                foreach ($errors as $error) {
                    fputcsv($stream, array_map([$normalizer, 'escapeForSpreadsheetExport'], [
                        $error->row_number,
                        $error->field,
                        $error->code,
                        $error->severity,
                        $error->message,
                        $error->is_resolved ? 'yes' : 'no',
                    ]));
                }
            });
            fclose($stream);
        }, "customer-import-{$import}-errors.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function viewData(Request $request, array $extra = []): View
    {
        $countries = DB::table('countries as c')
            ->leftJoin('customer_accounts as a', 'a.country_id', '=', 'c.id')
            ->leftJoin('customer_contacts as ct', 'ct.customer_account_id', '=', 'a.id')
            ->leftJoin('contact_email_addresses as e', 'e.customer_contact_id', '=', 'ct.id')
            ->leftJoin('customer_invoice_references as i', 'i.customer_account_id', '=', 'a.id')
            ->selectRaw('c.id, c.name, c.iso_code_2, c.region, count(distinct a.id) as companies, count(distinct ct.id) as contacts, count(distinct case when e.is_valid = 1 then e.id end) as valid_emails, count(distinct case when ct.marketing_status = ? then ct.id end) as marketable, count(distinct case when ct.marketing_status in (?, ?) then ct.id end) as transactional_only, count(distinct i.id) as invoice_references', ['opted_in', 'unknown', 'transactional_only'])
            ->groupBy('c.id', 'c.name', 'c.iso_code_2', 'c.region')
            ->havingRaw('count(distinct a.id) > 0')
            ->orderBy('c.name')
            ->get();

        return view('admin.marketing.customer-imports', $extra + [
            'imports' => DB::table('customer_imports')->orderByDesc('id')->paginate(20),
            'countries' => $countries,
            'profiles' => array_keys(config('customer_import.profiles', [])),
            'marketplaces' => DB::table('marketplaces')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'preview' => null,
            'token' => null,
        ]);
    }
}
