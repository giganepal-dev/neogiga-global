<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarehouseShipment;
use App\Models\WarehouseProduct;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseShipmentController extends Controller
{
    /**
     * Display a listing of shipments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WarehouseShipment::query();

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->has('to_warehouse_id')) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        if ($request->boolean('cross_border')) {
            $query->whereHas('fromWarehouse', function ($q) {
                $q->where('allows_cross_border', true);
            });
        }

        $perPage = $request->get('per_page', 15);
        $shipments = $query->with(['fromWarehouse', 'toWarehouse', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $shipments,
        ]);
    }

    /**
     * Store a newly created shipment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|uuid|exists:warehouses,id',
            'to_warehouse_id' => 'required|uuid|exists:warehouses,id|different:from_warehouse_id',
            'type' => 'required|in:transfer,inbound,outbound,return',
            'carrier' => 'nullable|string',
            'tracking_number' => 'nullable|string',
            'expected_departure_date' => 'nullable|date',
            'expected_arrival_date' => 'nullable|date|after_or_equal:expected_departure_date',
            'customs_documents' => 'nullable|array',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.product_variant_id' => 'nullable|uuid|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Create shipment
            $shipment = WarehouseShipment::create([
                'id' => (string) Str::uuid(),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'type' => $validated['type'],
                'status' => 'pending',
                'carrier' => $validated['carrier'] ?? null,
                'tracking_number' => $validated['tracking_number'] ?? null,
                'expected_departure_date' => $validated['expected_departure_date'] ?? null,
                'expected_arrival_date' => $validated['expected_arrival_date'] ?? null,
                'customs_documents' => $validated['customs_documents'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create shipment items
            foreach ($validated['items'] as $item) {
                $shipment->items()->create([
                    'id' => (string) Str::uuid(),
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? null,
                    'unit_price' => $item['unit_price'] ?? null,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);
            }

            // Update total items
            $shipment->updateStatus();
        });

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully',
            'data' => WarehouseShipment::with(['fromWarehouse', 'toWarehouse', 'items.product'])->find($shipment->id),
        ], 201);
    }

    /**
     * Display the specified shipment.
     */
    public function show(WarehouseShipment $shipment): JsonResponse
    {
        $shipment->load(['fromWarehouse', 'toWarehouse', 'items.product']);

        return response()->json([
            'success' => true,
            'data' => $shipment,
        ]);
    }

    /**
     * Update shipment status.
     */
    public function updateStatus(Request $request, WarehouseShipment $shipment): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_transit,delivered,cancelled',
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'in_transit') {
            $updateData['actual_departure_at'] = now();
        }

        if ($validated['status'] === 'delivered') {
            $updateData['actual_arrival_at'] = now();
            
            // Process inventory transfer
            $this->processInventoryTransfer($shipment);
        }

        $shipment->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Shipment status updated successfully',
            'data' => $shipment->fresh(['fromWarehouse', 'toWarehouse', 'items.product']),
        ]);
    }

    /**
     * Process inventory transfer when shipment is delivered.
     */
    protected function processInventoryTransfer(WarehouseShipment $shipment): void
    {
        if ($shipment->type !== 'transfer') {
            return;
        }

        foreach ($shipment->items as $item) {
            // Decrease from source warehouse
            $sourceProduct = WarehouseProduct::where('warehouse_id', $shipment->from_warehouse_id)
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($sourceProduct) {
                $sourceProduct->decrement('quantity_available', $item->quantity);
            }

            // Increase to destination warehouse
            $destProduct = WarehouseProduct::where('warehouse_id', $shipment->to_warehouse_id)
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($destProduct) {
                $destProduct->addStock($item->quantity, $item->unit_cost);
            } else {
                // Create new warehouse product entry
                WarehouseProduct::create([
                    'id' => (string) Str::uuid(),
                    'warehouse_id' => $shipment->to_warehouse_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity_available' => $item->quantity,
                    'cost_price' => $item->unit_cost,
                ]);
            }
        }
    }

    /**
     * Remove the specified shipment.
     */
    public function destroy(WarehouseShipment $shipment): JsonResponse
    {
        if ($shipment->status !== 'pending' && $shipment->status !== 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete shipment that is not in pending or cancelled status',
            ], 422);
        }

        $shipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully',
        ]);
    }
}
