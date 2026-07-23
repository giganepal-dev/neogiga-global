<?php

namespace App\Http\Controllers\Api\Freight;

use App\Http\Controllers\Controller;
use App\Models\Freight\FreightShipment;
use App\Models\Freight\Carrier;
use App\Models\Freight\LandedCostAllocation;
use App\Services\Freight\LandedCostService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FreightController extends Controller
{
    public function __construct(
        protected LandedCostService $landedCostService
    ) {}

    /**
     * List freight shipments
     */
    public function index(Request $request): JsonResponse
    {
        $query = FreightShipment::with(['warehouse', 'carrier', 'supplier']);

        if ($request->has('type')) {
            $query->where('shipment_type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $shipments = $query->paginate($request->get('per_page', 20));

        return response()->json($shipments);
    }

    /**
     * Create freight shipment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_type' => 'required|in:inbound,outbound',
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'carrier_id' => 'nullable|exists:carriers,id',
            'awb_number' => 'nullable|string',
            'bl_number' => 'nullable|string',
            'container_number' => 'nullable|string',
            'origin_country' => 'nullable|string',
            'destination_country' => 'nullable|string',
            'incoterm' => 'nullable|string',
            'gross_weight' => 'nullable|numeric|min:0',
            'volume_cbm' => 'nullable|numeric|min:0',
            'package_count' => 'nullable|integer|min:0',
            'freight_cost' => 'nullable|numeric|min:0',
            'insurance_cost' => 'nullable|numeric|min:0',
            'customs_duty' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'expected_arrival_date' => 'nullable|date',
        ]);

        $validated['shipment_number'] = 'SHP-' . date('Ymd') . '-' . str_pad((string) (FreightShipment::count() + 1), 4, '0', STR_PAD_LEFT);
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        $shipment = FreightShipment::create($validated);

        return response()->json($shipment, 201);
    }

    /**
     * Get shipment details
     */
    public function show(FreightShipment $shipment): JsonResponse
    {
        $shipment->load(['expenses', 'landedCostAllocations.product', 'carrier', 'supplier', 'warehouse']);

        return response()->json($shipment);
    }

    /**
     * Update shipment
     */
    public function update(Request $request, FreightShipment $shipment): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,in_transit,arrived,cleared,delivered',
            'actual_departure_date' => 'nullable|date',
            'actual_arrival_date' => 'nullable|date',
            'tracking_number' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $shipment->update($validated);

        return response()->json($shipment);
    }

    /**
     * Allocate landed costs
     */
    public function allocateLandedCost(Request $request, FreightShipment $shipment): JsonResponse
    {
        $validated = $request->validate([
            'method' => 'nullable|in:weight,volume,value,quantity',
        ]);

        $method = $validated['method'] ?? 'weight';

        $result = $this->landedCostService->allocateLandedCost($shipment, $method);

        if ($result['success']) {
            return response()->json([
                'message' => 'Landed costs allocated successfully',
                'allocations' => $result['allocations'],
                'total_cost' => $result['total_cost'],
            ]);
        }

        return response()->json(['message' => $result['message']], 422);
    }

    /**
     * Post landed costs to inventory
     */
    public function postLandedCost(FreightShipment $shipment): JsonResponse
    {
        $result = $this->landedCostService->postAllForShipment($shipment);

        return response()->json([
            'message' => 'Landed cost posting completed',
            'success' => $result['success'],
            'failed' => $result['failed'],
            'total' => $result['total'],
        ]);
    }

    /**
     * List carriers
     */
    public function carriers(): JsonResponse
    {
        $carriers = Carrier::active()->orderBy('name')->get();

        return response()->json($carriers);
    }
}
