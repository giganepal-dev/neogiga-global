<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\WarehouseService;
use App\Models\VendorWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all warehouses for seller
     */
    public function index()
    {
        $vendorId = Auth::id();
        
        $warehouses = VendorWarehouse::where('vendor_id', $vendorId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }

    /**
     * Get single warehouse
     */
    public function show(VendorWarehouse $warehouse)
    {
        $vendorId = Auth::id();

        if ($warehouse->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $warehouse->load(['documents']),
        ]);
    }

    /**
     * Create new warehouse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:owned,leased,3pl',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'required|string',
            'marketplace_coverage' => 'nullable|array',
            'operating_hours' => 'nullable|array',
            'dispatch_cutoff_time' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:10240',
        ]);

        try {
            $warehouse = $this->warehouseService->create($validated, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Warehouse created successfully. Submitted for verification.',
                'data' => $warehouse,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create warehouse: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update warehouse
     */
    public function update(Request $request, VendorWarehouse $warehouse)
    {
        $vendorId = Auth::id();

        if ($warehouse->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'nullable|string|in:owned,leased,3pl',
            'address_line1' => 'sometimes|required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|max:2',
            'contact_name' => 'sometimes|required|string|max:255',
            'contact_email' => 'sometimes|required|email',
            'contact_phone' => 'sometimes|required|string',
            'marketplace_coverage' => 'nullable|array',
            'operating_hours' => 'nullable|array',
            'dispatch_cutoff_time' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:10240',
        ]);

        try {
            $updated = $this->warehouseService->update($warehouse, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Warehouse updated successfully.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update warehouse: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Submit warehouse for verification
     */
    public function submitForVerification(VendorWarehouse $warehouse)
    {
        $vendorId = Auth::id();

        if ($warehouse->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $this->warehouseService->submitForVerification($warehouse);

            return response()->json([
                'success' => true,
                'message' => 'Warehouse submitted for verification.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete warehouse (only if not used)
     */
    public function destroy(VendorWarehouse $warehouse)
    {
        $vendorId = Auth::id();

        if ($warehouse->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if warehouse has inventory or orders
        $hasInventory = $warehouse->offers()->exists();
        $hasOrders = \App\Models\VendorOrder::whereHas('items', function ($q) use ($warehouse) {
            $q->where('warehouse_id', $warehouse->id);
        })->exists();

        if ($hasInventory || $hasOrders) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete warehouse with existing inventory or orders.',
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully.',
        ]);
    }
}
