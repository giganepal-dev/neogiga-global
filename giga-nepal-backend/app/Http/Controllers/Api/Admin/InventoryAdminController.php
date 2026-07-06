<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Inventory\PurchaseReceivingService;
use App\Services\Inventory\StockMovementService;
use App\Services\Inventory\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryAdminController extends Controller
{
    use ApiResponses;

    public function overview(): JsonResponse
    {
        return $this->success([
            'warehouses' => DB::table('warehouses')->count(),
            'stock_rows' => DB::table('inventory_stocks')->count(),
            'available_units' => (int) DB::table('inventory_stocks')->sum('quantity_available'),
            'reserved_units' => (int) DB::table('inventory_stocks')->sum('quantity_reserved'),
            'low_stock_rows' => DB::table('inventory_stocks')->whereColumn('quantity_available', '<=', 'reorder_point')->count(),
            'movements' => DB::table('inventory_movements')->count(),
        ]);
    }

    public function stocks(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 25), 100));

        return $this->success(DB::table('inventory_stocks as s')
            ->leftJoin('products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->select('s.*', 'p.name as product_name', 'p.sku as product_sku', 'w.name as warehouse_name', 'w.code as warehouse_code')
            ->orderByDesc('s.id')
            ->paginate($perPage));
    }

    public function movements(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 25), 100));

        return $this->success(DB::table('inventory_movements')->orderByDesc('id')->paginate($perPage));
    }

    public function adjust(Request $request, StockMovementService $movements): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity_change' => ['required', 'integer'],
            'movement_type' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'allow_negative' => ['nullable', 'boolean'],
        ]);

        try {
            return $this->success($movements->adjust($data), 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function transfer(Request $request, TransferService $transfers): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            return $this->success($transfers->transfer($data), 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function receive(Request $request, PurchaseReceivingService $receiving): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'purchase_order_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        return $this->success($receiving->receive($data), 201);
    }

    public function lowStock(): JsonResponse
    {
        return $this->success(DB::table('inventory_stocks as s')
            ->leftJoin('products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->whereColumn('s.quantity_available', '<=', 's.reorder_point')
            ->select('s.*', 'p.name as product_name', 'p.sku as product_sku', 'w.name as warehouse_name')
            ->orderBy('s.quantity_available')
            ->limit(100)
            ->get());
    }
}
