<?php

namespace App\Http\Controllers\Admin;

use App\Catalog\Ingestion\Normalizers\CatalogNormalizer;
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
        $sources = DB::table('catalog_sources')->orderBy('name')->get();
        $tasks = DB::table('catalog_review_tasks as task')
            ->leftJoin('catalog_sources as source', 'source.id', '=', 'task.catalog_source_id')
            ->leftJoin('supplier_products as supplier_product', 'supplier_product.id', '=', 'task.supplier_product_id')
            ->leftJoin('products as product', 'product.id', '=', 'task.product_id')
            ->select('task.*', 'source.code as source_code', 'source.name as source_name', 'supplier_product.supplier_sku', 'supplier_product.source_name as supplier_product_name', 'supplier_product.source_brand', 'supplier_product.source_manufacturer', 'supplier_product.manufacturer_part_number', 'supplier_product.data_quality_score', 'product.sku as product_sku', 'product.name as product_name')
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
        if (($source->source_type ?? 'supplier') === 'supplier_document' && ($importEnabled || $mediaEnabled)) {
            return back()->withErrors(['import_enabled' => 'Document sources cannot enable crawling or media downloads.'])->withInput();
        }
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

    public function verifyIdentity(Request $request, int $task, CatalogNormalizer $normalizer): RedirectResponse
    {
        $record = DB::table('catalog_review_tasks')->where('id', $task)->first();
        abort_unless($record && $record->supplier_product_id && $record->product_id, 404);
        $data = $request->validate([
            'manufacturer' => ['required', 'string', 'min:2', 'max:160'],
            'mpn' => ['required', 'string', 'min:2', 'max:160'],
            'note' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        $manufacturer = $normalizer->text($data['manufacturer']);
        $mpn = $normalizer->mpn($data['mpn']);
        if (! $manufacturer || ! $mpn) {
            return back()->withErrors(['manufacturer' => 'A valid manufacturer and MPN are required.'])->withInput();
        }

        DB::transaction(function () use ($record, $manufacturer, $mpn, $data, $request, $normalizer): void {
            $brandId = $this->verifiedBrand($manufacturer, $normalizer);
            $existingProduct = $this->findCanonicalMatch($brandId, $mpn, (int) $record->product_id);
            $product = DB::table('products')->where('id', $record->product_id)->first();
            abort_unless($product, 404);
            $metadata = is_string($product->metadata ?? null) ? json_decode($product->metadata, true) : (array) ($product->metadata ?? []);
            $metadata['identity_review'] = [
                'manufacturer' => $manufacturer,
                'mpn' => $mpn,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now()->toIso8601String(),
                'note' => $data['note'],
                'possible_canonical_match_id' => $existingProduct?->id,
            ];
            DB::table('products')->where('id', $record->product_id)->update([
                'brand_id' => $brandId,
                'mpn' => $mpn,
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);
            DB::table('supplier_products')->where('id', $record->supplier_product_id)->update([
                'manufacturer_part_number' => $mpn,
                'source_brand' => $manufacturer,
                'source_manufacturer' => $manufacturer,
                'updated_at' => now(),
            ]);
            $evidence = is_string($record->evidence_json ?? null) ? json_decode($record->evidence_json, true) : (array) ($record->evidence_json ?? []);
            $evidence['identity_review'] = $metadata['identity_review'];
            DB::table('catalog_review_tasks')->where('id', $record->id)->update([
                'status' => 'resolved',
                'assigned_to' => $request->user()?->id,
                'resolved_at' => now(),
                'evidence_json' => json_encode($evidence),
                'updated_at' => now(),
            ]);
            DB::table('catalog_review_tasks')->insert([
                'catalog_source_id' => $record->catalog_source_id,
                'supplier_product_id' => $record->supplier_product_id,
                'product_id' => $record->product_id,
                'task_type' => $existingProduct ? 'possible_canonical_duplicate' : 'supplier_product_review',
                'status' => 'open',
                'confidence' => $existingProduct ? 0.95 : 0.8,
                'evidence_json' => json_encode([
                    'identity_reviewed' => true,
                    'manufacturer' => $manufacturer,
                    'mpn' => $mpn,
                    'possible_canonical_match_id' => $existingProduct?->id,
                    'review_note' => $data['note'],
                    'next_action' => $existingProduct ? 'Review duplicate candidate; no merge was performed.' : 'Review source data before product approval.',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        $this->auditLog($request, 'catalog_identity_verified', ['task_id' => $task, 'manufacturer' => $manufacturer, 'mpn' => $mpn, 'note' => $data['note']]);

        return back()->with('status', "Identity recorded for review task #{$task}. Product remains hidden pending approval.");
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

    private function verifiedBrand(string $manufacturer, CatalogNormalizer $normalizer): int
    {
        $slug = $normalizer->slug($manufacturer);
        $brand = DB::table('product_brands')->where('slug', $slug)->first();
        if ($brand) {
            return (int) $brand->id;
        }

        return DB::table('product_brands')->insertGetId([
            'name' => $manufacturer,
            'slug' => $slug,
            'is_active' => false,
            'is_featured' => false,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findCanonicalMatch(int $brandId, string $mpn, int $exceptProductId): ?object
    {
        $normalizedMpnExpression = DB::connection()->getDriverName() === 'pgsql'
            ? "upper(regexp_replace(coalesce(mpn, ''), '\\s+', '', 'g'))"
            : "upper(replace(coalesce(mpn, ''), ' ', ''))";

        return DB::table('products')
            ->where('brand_id', $brandId)
            ->where('id', '!=', $exceptProductId)
            ->whereRaw("{$normalizedMpnExpression} = ?", [$mpn])
            ->first();
    }
}
