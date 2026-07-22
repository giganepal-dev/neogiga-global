<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\Driver;
use App\Models\Freight\ProofOfDelivery;
use App\Services\Dispatch\DispatchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DispatchController extends Controller
{
    public function __construct(
        protected DispatchService $dispatchService
    ) {}

    /**
     * List dispatch batches
     */
    public function index(Request $request): JsonResponse
    {
        $query = DispatchBatch::with(['warehouse', 'carrier', 'assignedTo']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->scheduled_date);
        }

        $batches = $query->paginate($request->get('per_page', 20));

        return response()->json($batches);
    }

    /**
     * Create dispatch batch
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'route_code' => 'nullable|string',
            'carrier_id' => 'nullable|exists:carriers,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $batch = $this->dispatchService->createDispatchBatch(
            \App\Models\Warehouse\Warehouse::findOrFail($validated['warehouse_id']),
            $validated['order_ids'],
            $validated['route_code'] ?? null,
            $validated['carrier_id'] ?? null,
            $validated['assigned_to'] ?? null
        );

        return response()->json($batch, 201);
    }

    /**
     * Get batch details
     */
    public function show(DispatchBatch $batch): JsonResponse
    {
        $batch->load(['items.product', 'packages', 'proofOfDeliveries.driver']);

        return response()->json($batch);
    }

    /**
     * Pick items in a batch
     */
    public function pickItems(Request $request, DispatchBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:dispatch_items,id',
        ]);

        $this->dispatchService->pickItems($batch, $validated['item_ids']);

        return response()->json(['message' => 'Items picked successfully']);
    }

    /**
     * Pack items in a batch
     */
    public function packItems(Request $request, DispatchBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'packages' => 'required|array|min:1',
            'packages.*.order_id' => 'required|exists:orders,id',
            'packages.*.package_number' => 'nullable|string',
            'packages.*.length' => 'nullable|numeric',
            'packages.*.width' => 'nullable|numeric',
            'packages.*.height' => 'nullable|numeric',
            'packages.*.weight' => 'nullable|numeric',
            'packages.*.package_type' => 'nullable|string',
            'packages.*.tracking_number' => 'nullable|string',
            'packages.*.contents' => 'nullable|array',
        ]);

        $this->dispatchService->packItems($batch, $validated['packages']);

        return response()->json(['message' => 'Items packed successfully']);
    }

    /**
     * Dispatch batch for delivery
     */
    public function dispatch(Request $request, DispatchBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        $this->dispatchService->dispatch($batch, $validated['driver_id'] ?? null);

        return response()->json(['message' => 'Batch dispatched successfully']);
    }

    /**
     * Complete delivery with proof
     */
    public function completeDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pod_id' => 'required|exists:proof_of_deliveries,id',
            'status' => 'required|in:delivered,failed,returned',
            'recipient_name' => 'nullable|string',
            'signature' => 'nullable|string',
            'photos' => 'nullable|array',
            'notes' => 'nullable|string',
            'cod_amount' => 'nullable|numeric|min:0',
        ]);

        $this->dispatchService->completeDelivery(
            $validated['pod_id'],
            $validated['status'],
            $validated['recipient_name'] ?? null,
            $validated['signature'] ?? null,
            $validated['photos'] ?? null,
            $validated['notes'] ?? null,
            $validated['cod_amount'] ?? null
        );

        return response()->json(['message' => 'Delivery completed successfully']);
    }

    /**
     * List drivers
     */
    public function drivers(): JsonResponse
    {
        $drivers = Driver::available()->orderBy('name')->get();

        return response()->json($drivers);
    }

    /**
     * Get pending deliveries for driver
     */
    public function driverDeliveries(Request $request, Driver $driver): JsonResponse
    {
        $deliveries = ProofOfDelivery::where('driver_id', $driver->id)
            ->whereIn('status', ['in_transit', 'out_for_delivery'])
            ->with(['order.customer', 'dispatchBatch'])
            ->get();

        return response()->json($deliveries);
    }
}
