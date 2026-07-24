<?php

namespace App\Http\Controllers\Api\Freight;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FreightController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('freight_shipments')
            ->where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $shipments = $query->latest()->paginate(25);

        return $this->success($shipments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'carrier' => ['sometimes', 'nullable', 'string', 'max:100'],
            'service_level' => ['sometimes', 'nullable', 'string', 'max:100'],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'special_instructions' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $subtotal = collect($validated['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

        $id = DB::table('freight_shipments')->insertGetId([
            'shipment_number' => 'FRT-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6)),
            'user_id' => $request->user()->id,
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'carrier' => $validated['carrier'] ?? null,
            'service_level' => $validated['service_level'] ?? null,
            'weight_kg' => $validated['weight_kg'],
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => $subtotal,
            'currency_code' => 'USD',
            'status' => 'draft',
            'items' => json_encode($validated['items']),
            'special_instructions' => $validated['special_instructions'] ?? null,
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shipment = DB::table('freight_shipments')->where('id', $id)->first();

        return $this->success($shipment, 201);
    }

    public function show(int $shipment): JsonResponse
    {
        $record = DB::table('freight_shipments')
            ->where('id', $shipment)
            ->first();

        if (! $record) {
            return $this->error('Freight shipment not found.', 404);
        }

        return $this->success($record);
    }

    public function update(Request $request, int $shipment): JsonResponse
    {
        $record = DB::table('freight_shipments')->where('id', $shipment)->first();

        if (! $record) {
            return $this->error('Freight shipment not found.', 404);
        }

        $validated = $request->validate([
            'carrier' => ['sometimes', 'nullable', 'string', 'max:100'],
            'service_level' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:draft,quoted,booked,in_transit,delivered,cancelled'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'special_instructions' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $validated['updated_at'] = now();

        DB::table('freight_shipments')->where('id', $shipment)->update($validated);

        $updated = DB::table('freight_shipments')->where('id', $shipment)->first();

        return $this->success($updated);
    }

    public function allocateLandedCost(Request $request, int $shipment): JsonResponse
    {
        $record = DB::table('freight_shipments')->where('id', $shipment)->first();

        if (! $record) {
            return $this->error('Freight shipment not found.', 404);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.allocated_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $totalAllocated = collect($validated['items'])->sum('allocated_amount');

        DB::table('freight_shipments')->where('id', $shipment)->update([
            'landed_costs' => json_encode($validated['items']),
            'updated_at' => now(),
        ]);

        return $this->success([
            'shipment_id' => $shipment,
            'total_allocated' => $totalAllocated,
            'items' => $validated['items'],
        ]);
    }

    public function postLandedCost(Request $request, int $shipment): JsonResponse
    {
        $record = DB::table('freight_shipments')->where('id', $shipment)->first();

        if (! $record) {
            return $this->error('Freight shipment not found.', 404);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:booked,in_transit,delivered'],
        ]);

        $newStatus = $validated['status'] ?? 'booked';

        DB::table('freight_shipments')->where('id', $shipment)->update([
            'status' => $newStatus,
            'posted_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success([
            'shipment_id' => $shipment,
            'status' => $newStatus,
            'posted_at' => now()->toDateTimeString(),
        ]);
    }

    public function carriers(): JsonResponse
    {
        $carriers = DB::table('freight_carriers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success($carriers);
    }
}
