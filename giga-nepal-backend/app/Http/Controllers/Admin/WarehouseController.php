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
            'fulfillment_centers' => Warehouse::where('is_fulfillment_center', true)->count(),
            'cross_border_enabled' => Warehouse::where('allows_cross_border', true)->count(),
            
            // Regional breakdown
            'east_asia_warehouses' => Warehouse::where('region', 'East Asia')->count(),
            'south_asia_warehouses' => Warehouse::where('region', 'South Asia')->count(),
            'middle_east_warehouses' => Warehouse::where('region', 'Middle East')->count(),
            
            // Country breakdown
            'china_warehouses' => Warehouse::where('country', 'China')->count(),
            'india_warehouses' => Warehouse::where('country', 'India')->count(),
            'nepal_warehouses' => Warehouse::where('country', 'Nepal')->count(),
            'sri_lanka_warehouses' => Warehouse::where('country', 'Sri Lanka')->count(),
            
            // Capacity metrics
            'total_capacity_units' => Warehouse::sum('capacity_units'),
            'total_current_stock_units' => Warehouse::sum('current_stock_units'),
            'capacity_utilization_percent' => Warehouse::sum('capacity_units') > 0 
                ? round((Warehouse::sum('current_stock_units') / Warehouse::sum('capacity_units')) * 100, 2) 
                : 0,
            
            // Inventory metrics
            'total_products' => WarehouseProduct::sum('quantity_available'),
            'total_reserved' => WarehouseProduct::sum('quantity_reserved'),
            
            // Shipment metrics
            'pending_shipments' => WarehouseShipment::where('status', 'pending')->count(),
            'in_transit_shipments' => WarehouseShipment::where('status', 'in_transit')->count(),
            'delivered_shipments' => WarehouseShipment::where('status', 'delivered')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get distribution centers by region.
     */
    public function distributionCenters(Request $request): JsonResponse
    {
        $query = Warehouse::where('is_distribution_center', true)
            ->where('status', 'active');

        if ($request->has('region')) {
            $query->where('region', $request->region);
        }

        $warehouses = $query->withCount(['products'])
            ->orderBy('capacity_units', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }

    /**
     * Get warehouses by country.
     */
    public function byCountry(string $country): JsonResponse
    {
        $warehouses = Warehouse::where('country', $country)
            ->where('status', 'active')
            ->withCount(['products', 'outboundShipments', 'inboundShipments'])
            ->orderBy('city')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }
}
