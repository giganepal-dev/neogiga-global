<?php

namespace App\Http\Controllers\Api\Admin;

use App\Catalog\Ingestion\Validation\SupplierPolicyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogIngestionController extends Controller
{
    public function sources(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => DB::table('catalog_sources')
            ->whereIn('code', array_keys(config('catalog_import.suppliers')))->orderBy('name')->get()]);
    }

    public function runs(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => DB::table('catalog_import_runs as r')
            ->join('catalog_sources as s', 's.id', '=', 'r.catalog_source_id')->select('r.*', 's.code as supplier_code', 's.name as supplier_name')
            ->when($request->query('supplier'), fn ($query, $supplier) => $query->where('s.code', $supplier))
            ->orderByDesc('r.created_at')->paginate(50)]);
    }

    public function reviewTasks(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => DB::table('catalog_review_tasks')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('status')->orderByDesc('created_at')->paginate(50)]);
    }

    public function audit(string $supplier, SupplierPolicyService $policy): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $policy->audit($supplier)]);
    }

    public function updateSource(Request $request, string $supplier): JsonResponse
    {
        $data = $request->validate([
            'import_enabled' => ['sometimes', 'boolean'],
            'media_download_enabled' => ['sometimes', 'boolean'],
            'description_reuse_status' => ['sometimes', 'in:unknown,not_permitted,permitted'],
            'status' => ['sometimes', 'in:pending_manual_review,approved,blocked,disabled'],
        ]);
        if (($data['import_enabled'] ?? false) && (($data['status'] ?? DB::table('catalog_sources')->where('code', $supplier)->value('status')) !== 'approved')) {
            return response()->json(['success' => false, 'message' => 'Supplier import cannot be enabled until its policy status is approved.'], 422);
        }
        DB::table('catalog_sources')->where('code', $supplier)->update($data + ['updated_at' => now()]);

        return response()->json(['success' => true, 'data' => DB::table('catalog_sources')->where('code', $supplier)->first()]);
    }
}
