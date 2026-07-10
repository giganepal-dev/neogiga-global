<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\WarehouseShipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
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

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('distribution_center')) {
            $query->where('is_distribution_center', true);
        }

        if ($request->boolean('fulfillment_center')) {
            $query->where('is_fulfillment_center', true);
        }

        $perPage = $request->get('per_page', 15);
        $warehouses = $query->withCount(['products', 'outboundShipments', 'inboundShipments'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $warehouses,
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
            'region' => 'required|string',
            'country' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone' => 'nullable|string',
            'currency_code' => 'nullable|string|size:3',
            'status' => 'nullable|in:active,inactive,maintenance',
            'contact_info' => 'nullable|array',
            'operating_hours' => 'nullable|array',
            'capacity_units' => 'nullable|integer|min:0',
            'is_distribution_center' => 'nullable|boolean',
            'is_fulfillment_center' => 'nullable|boolean',
            'allows_cross_border' => 'nullable|boolean',
        ]);

        $warehouse = Warehouse::create(array_merge($validated, [
            'id' => (string) Str::uuid(),
            'created_by' => auth()->id(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully',
            'data' => $warehouse->load(['products', 'outboundShipments', 'inboundShipments']),
        ], 201);
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load(['products.product', 'outboundShipments', 'inboundShipments']);

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
            'region' => 'sometimes|required|string',
            'country' => 'sometimes|required|string',
            'city' => 'sometimes|required|string',
            'address' => 'sometimes|required|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone' => 'nullable|string',
            'currency_code' => 'nullable|string|size:3',
            'status' => 'nullable|in:active,inactive,maintenance',
            'contact_info' => 'nullable|array',
            'operating_hours' => 'nullable|array',
            'capacity_units' => 'nullable|integer|min:0',
            'is_distribution_center' => 'nullable|boolean',
            'is_fulfillment_center' => 'nullable|boolean',
            'allows_cross_border' => 'nullable|boolean',
        ]);

        $warehouse->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

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
        // Check if warehouse has products
        if ($warehouse->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete warehouse with existing products',
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
    public function stats(): JsonResponse
    {
        $stats = [
            'total_warehouses' => Warehouse::count(),
            'active_warehouses' => Warehouse::where('status', 'active')->count(),
            'distribution_centers' => Warehouse::where('is_distribution_center', true)->count(),
            'middle_east_warehouses' => Warehouse::where('region', 'Middle East')->count(),
            'uae_warehouses' => Warehouse::where('country', 'United Arab Emirates')->orWhere('country', 'UAE')->count(),
            'total_products' => WarehouseProduct::sum('quantity_available'),
            'total_reserved' => WarehouseProduct::sum('quantity_reserved'),
            'pending_shipments' => WarehouseShipment::where('status', 'pending')->count(),
            'in_transit_shipments' => WarehouseShipment::where('status', 'in_transit')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get Middle East distribution centers.
     */
    public function middleEastCenters(): JsonResponse
    {
        $warehouses = Warehouse::where('region', 'Middle East')
            ->where('is_distribution_center', true)
            ->where('status', 'active')
            ->withCount(['products'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }
}
