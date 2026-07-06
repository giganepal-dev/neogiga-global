<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\InventoryStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponses;

    /**
     * Availability summary for a product (never expose cost data).
     */
    public function byProduct(int $product): JsonResponse
    {
        $stocks = InventoryStock::query()
            ->where('product_id', $product)
            ->with('warehouse:id,name,code')
            ->get(['id', 'product_id', 'variant_id', 'warehouse_id', 'quantity_available', 'quantity_reserved']);

        return $this->success([
            'product_id' => $product,
            'total_available' => (int) $stocks->sum('quantity_available'),
            'locations' => $stocks,
        ]);
    }

    public function byMarketplace(Request $request, int $marketplace): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $stocks = InventoryStock::query()
            ->where('marketplace_id', $marketplace)
            ->with('warehouse:id,name,code')
            ->paginate($validated['per_page'] ?? 50, ['id', 'product_id', 'variant_id', 'warehouse_id', 'marketplace_id', 'quantity_available', 'quantity_reserved']);

        return $this->success($stocks);
    }

    public function byWarehouse(Request $request, int $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $stocks = InventoryStock::query()
            ->where('warehouse_id', $warehouse)
            ->paginate($validated['per_page'] ?? 50, ['id', 'product_id', 'variant_id', 'warehouse_id', 'quantity_available', 'quantity_reserved']);

        return $this->success($stocks);
    }

    /**
     * Soft-reserve with TTL + oversell guard — Phase 1 (Blueprint §18).
     */
    public function reserve(): JsonResponse
    {
        return $this->notImplemented('Inventory reservation');
    }

    public function releaseReservation(): JsonResponse
    {
        return $this->notImplemented('Inventory reservation release');
    }
}
