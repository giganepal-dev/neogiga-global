<?php

namespace App\Services\Dispatch;

use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\DispatchItem;
use App\Models\Dispatch\Package;
use App\Models\Order;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    /**
     * Create a dispatch batch from pending orders
     */
    public function createDispatchBatch(
        Warehouse $warehouse,
        array $orderIds,
        ?string $routeCode = null,
        ?int $carrierId = null,
        ?int $assignedTo = null
    ): DispatchBatch {
        return DB::transaction(function () use ($warehouse, $orderIds, $routeCode, $carrierId, $assignedTo) {
            $batchNumber = 'DISP-' . date('Ymd') . '-' . str_pad((string) (DispatchBatch::count() + 1), 4, '0', STR_PAD_LEFT);
            
            $batch = DispatchBatch::create([
                'batch_number' => $batchNumber,
                'warehouse_id' => $warehouse->id,
                'marketplace_id' => $warehouse->marketplace_id,
                'scheduled_date' => now()->toDateString(),
                'status' => 'pending',
                'route_code' => $routeCode,
                'carrier_id' => $carrierId,
                'assigned_to' => $assignedTo,
                'total_orders' => count($orderIds),
                'total_items' => 0,
                'total_weight' => 0,
                'created_by' => auth()->id(),
            ]);

            $totalItems = 0;
            $totalWeight = 0;

            // Add items to dispatch batch
            foreach ($orderIds as $orderId) {
                $order = Order::findOrFail($orderId);
                
                foreach ($order->items as $item) {
                    DispatchItem::create([
                        'dispatch_batch_id' => $batch->id,
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouse->id,
                        'bin_id' => $item->bin_id ?? null,
                        'quantity' => $item->quantity,
                        'status' => 'pending',
                    ]);

                    $totalItems += $item->quantity;
                    $totalWeight += ($item->product->weight ?? 0) * $item->quantity;
                }

                // Update order status
                $order->update(['status' => 'processing']);
            }

            $batch->update([
                'total_items' => $totalItems,
                'total_weight' => $totalWeight,
            ]);

            return $batch;
        });
    }

    /**
     * Pick items for a dispatch batch
     */
    public function pickItems(DispatchBatch $batch, array $itemIds): bool
    {
        return DB::transaction(function () use ($batch, $itemIds) {
            $items = DispatchItem::whereIn('id', $itemIds)
                ->where('dispatch_batch_id', $batch->id)
                ->get();

            foreach ($items as $item) {
                $item->update([
                    'status' => 'picked',
                    'picked_by' => auth()->id(),
                    'picked_at' => now(),
                ]);

                // Reserve inventory
                \App\Models\Warehouse\InventoryReservation::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'bin_id' => $item->bin_id,
                    'quantity' => $item->quantity,
                    'reference_type' => DispatchItem::class,
                    'reference_id' => $item->id,
                    'status' => 'picked',
                ]);
            }

            // Check if all items are picked
            if ($batch->items()->where('status', '!=', 'picked')->count() === 0) {
                $batch->update(['status' => 'packed']);
            } elseif ($batch->status === 'pending') {
                $batch->update(['status' => 'picking']);
            }

            return true;
        });
    }

    /**
     * Pack items and create packages
     */
    public function packItems(DispatchBatch $batch, array $packagesData): bool
    {
        return DB::transaction(function () use ($batch, $packagesData) {
            foreach ($packagesData as $packageData) {
                Package::create([
                    'dispatch_batch_id' => $batch->id,
                    'order_id' => $packageData['order_id'],
                    'package_number' => $packageData['package_number'] ?? null,
                    'length' => $packageData['length'] ?? null,
                    'width' => $packageData['width'] ?? null,
                    'height' => $packageData['height'] ?? null,
                    'weight' => $packageData['weight'] ?? null,
                    'package_type' => $packageData['package_type'] ?? 'box',
                    'tracking_number' => $packageData['tracking_number'] ?? null,
                    'carrier_id' => $batch->carrier_id,
                    'contents' => $packageData['contents'] ?? null,
                ]);
            }

            // Update dispatch items status
            $batch->items()->update([
                'status' => 'packed',
                'packed_by' => auth()->id(),
                'packed_at' => now(),
            ]);

            $batch->update(['status' => 'ready']);

            return true;
        });
    }

    /**
     * Dispatch a batch for delivery
     */
    public function dispatch(DispatchBatch $batch, ?int $driverId = null): bool
    {
        return DB::transaction(function () use ($batch, $driverId) {
            $batch->update([
                'status' => 'dispatched',
            ]);

            // Update all related orders
            $orderIds = $batch->items()->pluck('order_id')->unique();
            Order::whereIn('id', $orderIds)->update([
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            // Create proof of delivery records
            foreach ($orderIds as $orderId) {
                \App\Models\Freight\ProofOfDelivery::create([
                    'order_id' => $orderId,
                    'dispatch_batch_id' => $batch->id,
                    'driver_id' => $driverId,
                    'status' => 'in_transit',
                ]);
            }

            return true;
        });
    }

    /**
     * Complete delivery with proof
     */
    public function completeDelivery(
        int $podId,
        string $status,
        ?string $recipientName = null,
        ?string $signature = null,
        ?array $photos = null,
        ?string $notes = null,
        ?float $codAmount = null
    ): bool {
        $pod = \App\Models\Freight\ProofOfDelivery::findOrFail($podId);

        $updateData = [
            'status' => $status,
            'delivered_at' => $status === 'delivered' ? now() : null,
            'recipient_name' => $recipientName,
            'recipient_signature' => $signature,
            'photos' => $photos,
            'delivery_notes' => $notes,
        ];

        if ($codAmount !== null) {
            $updateData['cod_amount'] = $codAmount;
            $updateData['cod_collected'] = $status === 'delivered';
            $updateData['cod_collected_at'] = $status === 'delivered' ? now() : null;
        }

        $pod->update($updateData);

        // Update order status
        $pod->order->update([
            'status' => $status === 'delivered' ? 'delivered' : 'delivery_failed',
            'delivered_at' => $status === 'delivered' ? now() : null,
        ]);

        // If COD collected, create collection record
        if ($codAmount !== null && $status === 'delivered') {
            \App\Models\Freight\CodCollection::create([
                'driver_id' => $pod->driver_id,
                'proof_of_delivery_id' => $pod->id,
                'amount' => $codAmount,
                'currency' => $pod->order->currency,
                'collection_date' => now(),
                'status' => 'pending',
            ]);
        }

        return true;
    }
}
