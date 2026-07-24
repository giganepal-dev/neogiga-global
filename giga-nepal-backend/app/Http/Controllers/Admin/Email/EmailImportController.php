<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailImportController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_import_jobs');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $imports = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.email.imports.index', compact('imports'));
    }

    public function create(): View
    {
        $groups = DB::table('email_groups')->orderBy('name')->get();

        return view('admin.email.imports.create', compact('groups'));
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'group_id' => ['nullable', 'integer', 'exists:email_groups,id'],
        ]);

        $file = $request->file('csv_file');
        $filename = 'imports/'.Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->storeAs('public', $filename);

        $rows = array_map('str_getcsv', file(storage_path("app/{$filename}")));
        $headers = array_shift($rows);

        $importId = DB::table('email_import_jobs')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'filename' => $file->getClientOriginalName(),
            'storage_path' => $filename,
            'group_id' => $request->input('group_id'),
            'total_rows' => count($rows),
            'status' => 'uploaded',
            'headers' => json_encode($headers),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/imports/{$importId}")->with('status', 'File uploaded. Review mapping and confirm import.');
    }

    public function preview(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'import_id' => ['required', 'integer', 'exists:email_import_jobs,id'],
            'mapping' => ['required', 'array'],
        ]);

        $import = DB::table('email_import_jobs')->find($data['import_id']);

        DB::table('email_import_jobs')->where('id', $data['import_id'])->update([
            'column_mapping' => json_encode($data['mapping']),
            'status' => 'mapped',
            'updated_at' => now(),
        ]);

        return redirect("/email/imports/{$data['import_id']}")->with('status', 'Mapping saved. Ready to process.');
    }

    public function process(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'import_id' => ['required', 'integer', 'exists:email_import_jobs,id'],
        ]);

        $import = DB::table('email_import_jobs')->find($data['import_id']);
        abort_unless($import, 404);

        $storagePath = storage_path("app/{$import->storage_path}");
        if (! file_exists($storagePath)) {
            return back()->with('error', 'Import file not found.');
        }

        $rows = array_map('str_getcsv', file($storagePath));
        $headers = array_shift($rows);
        $mapping = json_decode($import->column_mapping, true) ?? [];

        $successCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;

        DB::transaction(function () use ($rows, $headers, $mapping, $import, &$successCount, &$errorCount, &$duplicateCount) {
            foreach ($rows as $index => $row) {
                $rowData = array_combine($headers, $row);
                $email = $rowData[$mapping['email'] ?? 'email'] ?? null;

                if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errorCount++;
                    DB::table('email_import_row_errors')->insert([
                        'import_id' => $import->id,
                        'row_number' => $index + 1,
                        'email' => $email,
                        'error' => 'Invalid email address',
                        'created_at' => now(),
                    ]);
                    continue;
                }

                $exists = DB::table('email_subscribers')->where('email', $email)->exists();
                if ($exists) {
                    $duplicateCount++;
                    continue;
                }

                DB::table('email_subscribers')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'email' => $email,
                    'first_name' => $rowData[$mapping['first_name'] ?? 'first_name'] ?? null,
                    'last_name' => $rowData[$mapping['last_name'] ?? 'last_name'] ?? null,
                    'phone' => $rowData[$mapping['phone'] ?? 'phone'] ?? null,
                    'company' => $rowData[$mapping['company'] ?? 'company'] ?? null,
                    'country' => $rowData[$mapping['country'] ?? 'country'] ?? null,
                    'status' => 'active',
                    'source' => 'import',
                    'import_id' => $import->id,
                    'engagement_score' => 0,
                    'total_sent' => 0,
                    'total_opened' => 0,
                    'total_clicked' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($import->group_id) {
                    $subscriberId = DB::table('email_subscribers')->where('email', $email)->value('id');
                    if ($subscriberId) {
                        DB::table('email_group_subscriber')->insert([
                            'subscriber_id' => $subscriberId,
                            'group_id' => $import->group_id,
                            'assignment_source' => 'import',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $successCount++;
            }

            DB::table('email_import_jobs')->where('id', $import->id)->update([
                'status' => 'completed',
                'imported_count' => $successCount,
                'duplicate_count' => $duplicateCount,
                'error_count' => $errorCount,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect("/email/imports/{$import->id}")->with('status', "Import complete: {$successCount} added, {$duplicateCount} duplicates, {$errorCount} errors.");
    }

    public function show(int $import): View
    {
        $row = DB::table('email_import_jobs')->find($import);
        abort_unless($row, 404);

        $errors = DB::table('email_import_row_errors')
            ->where('import_id', $import)
            ->orderBy('row_number')
            ->get();

        return view('admin.email.imports.show', compact('row', 'errors'));
    }

    public function downloadSuccess(int $import)
    {
        $row = DB::table('email_import_jobs')->find($import);
        abort_unless($row, 404);

        $subscribers = DB::table('email_subscribers')
            ->where('import_id', $import)
            ->select('email', 'first_name', 'last_name', 'status')
            ->get();

        return $this->downloadCsv($subscribers, "import-{$import}-success.csv");
    }

    public function downloadErrors(int $import)
    {
        $row = DB::table('email_import_jobs')->find($import);
        abort_unless($row, 404);

        $errors = DB::table('email_import_row_errors')
            ->where('import_id', $import)
            ->select('row_number', 'email', 'error')
            ->get();

        return $this->downloadCsv($errors, "import-{$import}-errors.csv");
    }

    public function downloadDuplicates(int $import)
    {
        $row = DB::table('email_import_jobs')->find($import);
        abort_unless($row, 404);

        $duplicates = DB::table('email_import_rows')
            ->where('import_id', $import)
            ->where('is_duplicate', true)
            ->select('email', 'first_name', 'last_name')
            ->get();

        return $this->downloadCsv($duplicates, "import-{$import}-duplicates.csv");
    }

    private function downloadCsv($data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            if ($data->isNotEmpty()) {
                fputcsv($handle, array_keys((array) $data->first()));
                foreach ($data as $row) {
                    fputcsv($handle, (array) $row);
                }
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
