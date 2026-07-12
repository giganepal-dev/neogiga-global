<?php

namespace App\Http\Controllers\Admin;

use App\Catalog\Ingestion\Persistence\CatalogDocumentStagingService;
use App\Catalog\Ingestion\Validation\SupplierPolicyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CatalogIngestionAdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasTable('catalog_import_runs') && Schema::hasTable('catalog_review_tasks'), 404);
        $supplier = (string) $request->query('supplier', '');
        $taskStatus = (string) $request->query('task_status', 'open');
        $sources = DB::table('catalog_sources')->whereIn('code', array_keys(config('catalog_import.suppliers')))->orderBy('name')->get();
        $tasks = DB::table('catalog_review_tasks as task')
            ->leftJoin('catalog_sources as source', 'source.id', '=', 'task.catalog_source_id')
            ->leftJoin('supplier_products as supplier_product', 'supplier_product.id', '=', 'task.supplier_product_id')
            ->leftJoin('products as product', 'product.id', '=', 'task.product_id')
            ->select('task.*', 'source.code as source_code', 'source.name as source_name', 'supplier_product.supplier_sku', 'supplier_product.source_name as supplier_product_name', 'supplier_product.data_quality_score', 'product.sku as product_sku', 'product.name as product_name')
            ->when($supplier !== '', fn ($query) => $query->where('source.code', $supplier))
            ->when($taskStatus !== '', fn ($query) => $query->where('task.status', $taskStatus))
            ->orderByRaw("case when task.status = 'open' then 0 else 1 end")
            ->orderBy('task.created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.catalog-ingestion', [
            'sources' => $sources,
            'tasks' => $tasks,
            'runs' => DB::table('catalog_import_runs as run')->join('catalog_sources as source', 'source.id', '=', 'run.catalog_source_id')->select('run.*', 'source.code as source_code')->when($supplier !== '', fn ($query) => $query->where('source.code', $supplier))->orderByDesc('run.created_at')->limit(12)->get(),
            'stats' => [
                'sources' => $sources->count(),
                'policy_pending' => $sources->where('status', 'pending_manual_review')->count(),
                'open_tasks' => DB::table('catalog_review_tasks')->where('status', 'open')->count(),
                'low_quality' => DB::table('supplier_products')->where('data_quality_score', '<', 60)->count(),
            ],
            'filters' => ['supplier' => $supplier, 'task_status' => $taskStatus],
        ]);
    }

    public function audit(string $supplier, SupplierPolicyService $policy, Request $request): RedirectResponse
    {
        try {
            $result = $policy->audit($supplier);
            $this->auditLog($request, 'catalog_source_audited', ['supplier' => $supplier, 'result' => $result]);

            return back()->with('status', "{$supplier} policy audit recorded as {$result['policy_status']}.");
        } catch (\Throwable $exception) {
            return back()->with('error', "{$supplier} audit failed: {$exception->getMessage()}");
        }
    }

    public function updateSource(Request $request, string $supplier): RedirectResponse
    {
        $source = DB::table('catalog_sources')->where('code', $supplier)->first();
        abort_unless($source, 404);
        $data = $request->validate([
            'status' => ['required', 'in:pending_manual_review,approved,blocked,disabled'],
            'description_reuse_status' => ['required', 'in:unknown,not_permitted,permitted'],
            'import_enabled' => ['nullable', 'boolean'],
            'media_download_enabled' => ['nullable', 'boolean'],
            'media_rights_confirmed' => ['nullable', 'boolean'],
            'note' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        $importEnabled = (bool) ($data['import_enabled'] ?? false);
        $mediaEnabled = (bool) ($data['media_download_enabled'] ?? false);
        if ($importEnabled && $data['status'] !== 'approved') {
            return back()->withErrors(['import_enabled' => 'Import requires an approved supplier policy.'])->withInput();
        }
        if ($mediaEnabled && ($data['status'] !== 'approved' || ! ($data['media_rights_confirmed'] ?? false))) {
            return back()->withErrors(['media_download_enabled' => 'Media downloads require approved policy and confirmed media rights.'])->withInput();
        }
        $policy = is_string($source->catalogue_policy ?? null) ? json_decode($source->catalogue_policy, true) : (array) ($source->catalogue_policy ?? []);
        $policy['manual_review_note'] = $data['note'];
        $policy['manual_reviewed_at'] = now()->toIso8601String();
        $policy['media_rights_confirmed'] = (bool) ($data['media_rights_confirmed'] ?? false);
        DB::table('catalog_sources')->where('id', $source->id)->update([
            'status' => $data['status'], 'description_reuse_status' => $data['description_reuse_status'], 'import_enabled' => $importEnabled,
            'media_download_enabled' => $mediaEnabled, 'catalogue_policy' => json_encode($policy), 'updated_at' => now(),
        ]);
        $this->auditLog($request, 'catalog_source_policy_updated', ['supplier' => $supplier, 'status' => $data['status'], 'import_enabled' => $importEnabled, 'media_download_enabled' => $mediaEnabled, 'note' => $data['note']]);

        return back()->with('status', "{$supplier} source policy updated.");
    }

    public function resolveTask(Request $request, int $task): RedirectResponse
    {
        $record = DB::table('catalog_review_tasks')->where('id', $task)->first();
        abort_unless($record, 404);
        $data = $request->validate(['status' => ['required', 'in:open,deferred,resolved'], 'note' => ['required', 'string', 'min:3', 'max:1000']]);
        $evidence = is_string($record->evidence_json ?? null) ? json_decode($record->evidence_json, true) : (array) ($record->evidence_json ?? []);
        $evidence['review_note'] = $data['note'];
        $evidence['reviewed_at'] = now()->toIso8601String();
        DB::table('catalog_review_tasks')->where('id', $task)->update([
            'status' => $data['status'], 'assigned_to' => $request->user()?->id, 'resolved_at' => $data['status'] === 'resolved' ? now() : null,
            'evidence_json' => json_encode($evidence), 'updated_at' => now(),
        ]);
        $this->auditLog($request, 'catalog_review_task_updated', ['task_id' => $task, 'status' => $data['status'], 'note' => $data['note']]);

        return back()->with('status', "Review task #{$task} updated.");
    }

    public function stageDocument(Request $request, CatalogDocumentStagingService $staging): RedirectResponse
    {
        $data = $request->validate([
            'quotation_csv' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
            'dry_run' => ['nullable', 'boolean'],
        ]);
        $path = $data['quotation_csv']->store('catalog/staging/uploads', 'local');
        try {
            $report = $staging->stage(Storage::disk('local')->path($path), [
                'source' => 'sunny_okystar_quotation_files',
                'source_file' => $path,
                'actor_id' => $request->user()?->id,
                'dry_run' => (bool) ($data['dry_run'] ?? false),
            ]);
        } catch (\Throwable $exception) {
            return back()->withErrors(['quotation_csv' => $exception->getMessage()]);
        }
        $this->auditLog($request, 'supplier_quotation_csv_staged', [
            'source' => 'sunny_okystar_quotation_files',
            'run_id' => $report['run_id'],
            'mode' => $report['mode'],
            'status' => $report['status'],
            'counters' => $report['counters'],
        ]);

        return back()->with('status', "Quotation CSV {$report['status']}: ".number_format($report['counters']['products_discovered']).' rows reviewed.');
    }

    private function auditLog(Request $request, string $action, array $values): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }
        DB::table('audit_logs')->insert([
            'user_id' => $request->user()?->id, 'action' => $action, 'model_type' => 'catalog_ingestion', 'model_id' => null,
            'model_display_name' => $values['supplier'] ?? null, 'old_values' => null, 'new_values' => json_encode($values),
            'ip_address' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 1000), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
