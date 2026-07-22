<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailImport;
use App\Models\EmailGroup;
use App\Services\Email\Import\SubscriberImportService;
use App\Jobs\Email\Import\ProcessImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmailImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:email.subscribers.import');
    }

    public function index(Request $request)
    {
        $imports = EmailImport::with(['targetGroup', 'importedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.email.imports.index', compact('imports'));
    }

    public function create()
    {
        $groups = EmailGroup::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.email.imports.create', compact('groups'));
    }

    public function store(Request $request, SubscriberImportService $importService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:102400',
            'target_group_id' => 'nullable|exists:email_groups,id',
            'auto_assign_by_country' => 'boolean',
            'default_subscriber_type' => 'required|string',
            'default_source' => 'required|string',
            'duplicate_handling' => ['required', Rule::in(['skip', 'update', 'merge'])],
            'update_existing' => 'boolean',
            'skip_unsubscribed' => 'boolean',
            'skip_suppressed' => 'boolean',
            'validate_dns' => 'boolean',
        ]);

        $file = $request->file('file');
        $filename = 'import_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('email-imports', $filename, 'private');

        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = in_array($extension, ['xlsx', 'xls']) ? 'xlsx' : 'csv';

        $import = EmailImport::create([
            'name' => $validated['name'],
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $filename,
            'file_path' => $path,
            'file_type' => $fileType,
            'status' => EmailImport::STATUS_PENDING,
            'target_group_id' => $validated['target_group_id'] ?? null,
            'auto_assign_by_country' => $validated['auto_assign_by_country'] ?? false,
            'default_subscriber_type' => $validated['default_subscriber_type'],
            'default_source' => $validated['default_source'],
            'duplicate_handling' => $validated['duplicate_handling'],
            'update_existing' => $validated['update_existing'] ?? false,
            'skip_unsubscribed' => $validated['skip_unsubscribed'] ?? true,
            'skip_suppressed' => $validated['skip_suppressed'] ?? true,
            'validate_dns' => $validated['validate_dns'] ?? false,
            'imported_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'import' => $import,
            'message' => 'Import created successfully. Processing will begin shortly.',
        ]);
    }

    public function show(EmailImport $import)
    {
        $import->load(['targetGroup', 'importedBy']);

        $stats = [
            'total' => $import->total_rows,
            'valid' => $import->valid_rows,
            'imported' => $import->imported_rows,
            'updated' => $import->updated_rows,
            'duplicate' => $import->duplicate_rows,
            'invalid' => $import->invalid_email_rows,
            'failed' => $import->failed_rows,
            'progress' => $import->progress,
        ];

        return view('admin.email.imports.show', compact('import', 'stats'));
    }

    public function preview(EmailImport $import, Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 50;

        $rows = $import->rows()
            ->orderBy('row_number')
            ->paginate($perPage);

        return response()->json([
            'rows' => $rows,
            'stats' => [
                'total' => $import->total_rows,
                'valid' => $import->valid_rows,
                'invalid' => $import->invalid_email_rows,
                'pending' => $import->rows()->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function process(EmailImport $import)
    {
        if ($import->status !== EmailImport::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Import cannot be processed in its current status.',
            ], 400);
        }

        ProcessImportJob::dispatch($import)->onQueue('emails-import');

        return response()->json([
            'success' => true,
            'message' => 'Import processing started.',
        ]);
    }

    public function downloadErrors(EmailImport $import, SubscriberImportService $importService)
    {
        $filePath = $importService->generateErrorReport($import);

        if (!file_exists($filePath)) {
            abort(404, 'Error report not generated yet.');
        }

        return response()->download($filePath, "import_{$import->id}_errors.csv");
    }

    public function downloadReport(EmailImport $import)
    {
        $finalReport = $import->getFinalReportPath();
        $directory = dirname($finalReport);
        
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(['Row Number', 'Email', 'Status', 'Action', 'Subscriber ID']);

        $rows = $import->rows()->orderBy('row_number')->get();
        foreach ($rows as $row) {
            $csv->insertOne([
                $row->row_number,
                $row->email ?? 'N/A',
                $row->status,
                $row->action_taken,
                $row->subscriber_id ?? '',
            ]);
        }

        $csv->output(basename($finalReport));
        
        return response()->download($finalReport, "import_{$import->id}_report.csv");
    }

    public function cancel(EmailImport $import)
    {
        if (!in_array($import->status, [EmailImport::STATUS_PENDING, EmailImport::STATUS_PROCESSING])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel import in current status.',
            ], 400);
        }

        $import->update(['status' => EmailImport::STATUS_CANCELLED]);

        return response()->json([
            'success' => true,
            'message' => 'Import cancelled successfully.',
        ]);
    }

    public function destroy(EmailImport $import)
    {
        if (Storage::disk('private')->exists($import->file_path)) {
            Storage::disk('private')->delete($import->file_path);
        }

        $import->rows()->delete();
        $import->delete();

        return response()->json([
            'success' => true,
            'message' => 'Import record deleted successfully.',
        ]);
    }
}
