<?php

namespace App\Http\Controllers\Api\Dispatch;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DispatchController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('dispatch_batches')
            ->where('created_by', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $batches = $query->latest()->paginate(25);

        return $this->success($batches);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer'],
            'driver_id' => ['sometimes', 'nullable', 'integer'],
            'vehicle_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'scheduled_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $orders = DB::table('orders')
            ->whereIn('id', $validated['order_ids'])
            ->get();

        if ($orders->count() !== count($validated['order_ids'])) {
            return $this->error('One or more order IDs are invalid.', 422);
        }

        $totalWeight = 0;
        $itemCount = 0;

        foreach ($orders as $order) {
            $items = DB::table('order_items')->where('order_id', $order->id)->get();
            $itemCount += $items->count();
            foreach ($items as $item) {
                $totalWeight += (float) ($item->quantity ?? 1);
            }
        }

        $id = DB::table('dispatch_batches')->insertGetId([
            'batch_number' => 'DSP-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6)),
            'created_by' => $request->user()->id,
            'driver_id' => $validated['driver_id'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'order_ids' => json_encode($validated['order_ids']),
            'total_orders' => $orders->count(),
            'total_items' => $itemCount,
            'estimated_weight_kg' => $totalWeight,
            'status' => 'pending',
            'scheduled_date' => $validated['scheduled_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batch = DB::table('dispatch_batches')->where('id', $id)->first();

        return $this->success($batch, 201);
    }

    public function show(int $batch): JsonResponse
    {
        $record = DB::table('dispatch_batches')->where('id', $batch)->first();

        if (! $record) {
            return $this->error('Dispatch batch not found.', 404);
        }

        $orderIds = json_decode($record->order_ids, true) ?? [];
        $orders = DB::table('orders')
            ->whereIn('id', $orderIds)
            ->select('id', 'order_number', 'status', 'shipping_address', 'grand_total')
            ->get();

        return $this->success((array) $record + ['orders' => $orders]);
    }

    public function pickItems(Request $request, int $batch): JsonResponse
    {
        $record = DB::table('dispatch_batches')->where('id', $batch)->first();

        if (! $record) {
            return $this->error('Dispatch batch not found.', 404);
        }

        if ($record->status !== 'pending') {
            return $this->error('Batch must be in pending status to pick items.', 422);
        }

        $validated = $request->validate([
            'picked_items' => ['required', 'array', 'min:1'],
            'picked_items.*.order_id' => ['required', 'integer'],
            'picked_items.*.product_id' => ['required', 'integer'],
            'picked_items.*.quantity' => ['required', 'integer', 'min:1'],
            'picked_items.*.location' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        DB::table('dispatch_batches')->where('id', $batch)->update([
            'status' => 'picking',
            'picked_items' => json_encode($validated['picked_items']),
            'picked_at' => now(),
            'picked_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        return $this->success([
            'batch_id' => $batch,
            'status' => 'picking',
            'picked_items' => $validated['picked_items'],
        ]);
    }

    public function packItems(Request $request, int $batch): JsonResponse
    {
        $record = DB::table('dispatch_batches')->where('id', $batch)->first();

        if (! $record) {
            return $this->error('Dispatch batch not found.', 404);
        }

        if ($record->status !== 'picking') {
            return $this->error('Batch must be in picking status to pack items.', 422);
        }

        $validated = $request->validate([
            'packages' => ['required', 'array', 'min:1'],
            'packages.*.dimensions' => ['sometimes', 'nullable', 'string', 'max:100'],
            'packages.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'packages.*.carrier' => ['required', 'string', 'max:100'],
            'packages.*.service_level' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        DB::table('dispatch_batches')->where('id', $batch)->update([
            'status' => 'packing',
            'packages' => json_encode($validated['packages']),
            'packed_at' => now(),
            'packed_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        return $this->success([
            'batch_id' => $batch,
            'status' => 'packing',
            'packages' => $validated['packages'],
        ]);
    }

    public function dispatch(Request $request, int $batch): JsonResponse
    {
        $record = DB::table('dispatch_batches')->where('id', $batch)->first();

        if (! $record) {
            return $this->error('Dispatch batch not found.', 404);
        }

        if ($record->status !== 'packing') {
            return $this->error('Batch must be in packing status to dispatch.', 422);
        }

        DB::table('dispatch_batches')->where('id', $batch)->update([
            'status' => 'dispatched',
            'dispatched_at' => now(),
            'dispatched_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        $orderIds = json_decode($record->order_ids, true) ?? [];
        DB::table('orders')
            ->whereIn('id', $orderIds)
            ->update([
                'status' => 'shipped',
                'shipped_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->success([
            'batch_id' => $batch,
            'status' => 'dispatched',
            'dispatched_at' => now()->toDateTimeString(),
        ]);
    }

    public function completeDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:dispatch_batches,id'],
            'delivery_proof' => ['sometimes', 'nullable', 'string', 'max:500'],
            'recipient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $record = DB::table('dispatch_batches')->where('id', $validated['batch_id'])->first();

        if ($record->status !== 'dispatched') {
            return $this->error('Batch must be in dispatched status to complete delivery.', 422);
        }

        DB::table('dispatch_batches')->where('id', $validated['batch_id'])->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'delivery_proof' => $validated['delivery_proof'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'delivery_notes' => $validated['notes'] ?? null,
            'updated_at' => now(),
        ]);

        $orderIds = json_decode($record->order_ids, true) ?? [];
        DB::table('orders')
            ->whereIn('id', $orderIds)
            ->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->success([
            'batch_id' => $validated['batch_id'],
            'status' => 'delivered',
            'delivered_at' => now()->toDateTimeString(),
        ]);
    }

    public function drivers(): JsonResponse
    {
        $drivers = DB::table('users')
            ->where('role', 'driver')
            ->orWhere('role', 'delivery_agent')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return $this->success($drivers);
    }

    public function driverDeliveries(Request $request, int $driver): JsonResponse
    {
        $batches = DB::table('dispatch_batches')
            ->where('driver_id', $driver)
            ->latest()
            ->paginate(25);

        return $this->success($batches);
    }
}
