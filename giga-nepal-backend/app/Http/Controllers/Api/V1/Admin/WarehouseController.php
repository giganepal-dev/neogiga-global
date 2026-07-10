<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        // Filters
        if ($request->has('region')) {
            $query->where('region', $request->region);
        }

        if ($request->has('country')) {
            $query->where('country', $request->country);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('supports_cross_border')) {
            $query->where('supports_cross_border', $request->boolean('supports_cross_border'));
        }

        $perPage = $request->get('per_page', 15);
        $warehouses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $warehouses,
            'meta' => [
                'total_capacity' => Warehouse::sum('capacity_units'),
                'total_stock' => Warehouse::sum('current_stock_units'),
                'utilization_rate' => Warehouse::sum('current_stock_units') / max(1, Warehouse::sum('capacity_units')) * 100,
            ],
        ]);
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:warehouses,code',
            'type' => 'required|in:global_distribution,regional_distribution,main_distribution,fulfillment_center,cross_dock',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state_province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'country_name' => 'required|string|max:100',
            'region' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity_units' => 'required|integer|min:1',
            'manager_name' => 'nullable|string|max:255',
            'manager_email' => 'nullable|email|max:255',
            'manager_phone' => 'nullable|string|max:50',
            'operating_hours_start' => 'required|date_format:H:i',
            'operating_hours_end' => 'required|date_format:H:i|after:operating_hours_start',
            'timezone' => 'required|string|timezone',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'supports_cross_border' => 'boolean',
            'customs_clearance_enabled' => 'boolean',
            'cold_storage_available' => 'boolean',
            'hazmat_certified' => 'boolean',
        ]);

        $warehouse = Warehouse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully',
            'data' => $warehouse,
        ], 201);
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load(['inventoryItems', 'shipments.fromWarehouse', 'shipments.toWarehouse']);

        return response()->json([
            'success' => true,
            'data' => $warehouse,
        ]);
    }

    /**
     * Update the specified warehouse.
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:warehouses,code,' . $warehouse->id,
            'type' => 'sometimes|required|in:global_distribution,regional_distribution,main_distribution,fulfillment_center,cross_dock',
            'address_line_1' => 'sometimes|required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state_province' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|size:2',
            'country_name' => 'sometimes|required|string|max:100',
            'region' => 'sometimes|required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity_units' => 'sometimes|required|integer|min:1',
            'manager_name' => 'nullable|string|max:255',
            'manager_email' => 'nullable|email|max:255',
            'manager_phone' => 'nullable|string|max:50',
            'operating_hours_start' => 'sometimes|required|date_format:H:i',
            'operating_hours_end' => 'sometimes|required|date_format:H:i|after:operating_hours_start',
            'timezone' => 'sometimes|required|string|timezone',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'supports_cross_border' => 'boolean',
            'customs_clearance_enabled' => 'boolean',
            'cold_storage_available' => 'boolean',
            'hazmat_certified' => 'boolean',
        ]);

        $warehouse->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated successfully',
            'data' => $warehouse->fresh(),
        ]);
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        if ($warehouse->current_stock_units > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete warehouse with existing inventory',
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully',
        ]);
    }

    /**
     * Get warehouse statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = DB::table('warehouses')
            ->selectRaw('region, COUNT(*) as warehouse_count, SUM(capacity_units) as total_capacity, SUM(current_stock_units) as total_stock')
            ->groupBy('region')
            ->get();

        $globalStats = [
            'total_warehouses' => Warehouse::count(),
            'active_warehouses' => Warehouse::where('is_active', true)->count(),
            'total_capacity' => Warehouse::sum('capacity_units'),
            'total_stock' => Warehouse::sum('current_stock_units'),
            'utilization_rate' => round(Warehouse::sum('current_stock_units') / max(1, Warehouse::sum('capacity_units')) * 100, 2),
            'cross_border_enabled' => Warehouse::where('supports_cross_border', true)->count(),
            'customs_enabled' => Warehouse::where('customs_clearance_enabled', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'global' => $globalStats,
                'by_region' => $stats,
            ],
        ]);
    }

    /**
     * Get warehouses by region.
     */
    public function byRegion(string $region): JsonResponse
    {
        $warehouses = Warehouse::where('region', $region)
            ->where('is_active', true)
            ->orderBy('capacity_units', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }

    /**
     * Transfer inventory between warehouses.
     */
    public function transferInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|uuid|exists:warehouses,id',
            'to_warehouse_id' => 'required|uuid|exists:warehouses,id|different:from_warehouse_id',
            'product_id' => 'required|uuid|exists:products,id',
            'variant_id' => 'nullable|uuid|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $shipment = $this->warehouseService->createInterWarehouseShipment(
                $validated['from_warehouse_id'],
                $validated['to_warehouse_id'],
                $validated['product_id'],
                $validated['variant_id'] ?? null,
                $validated['quantity'],
                $validated['notes'] ?? null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory transfer initiated successfully',
                'data' => $shipment,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate transfer: ' . $e->getMessage(),
            ], 500);
        }
    }
}
