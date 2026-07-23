<?php

namespace App\Services;

use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use App\Models\SellerShipment;
use App\Models\SellerInventoryMovement;
use App\Models\SellerOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Get orders for a seller with filtering
     */
    public function getOrders(int $vendorId, array $filters = [])
    {
        $query = VendorOrder::whereHas('items.offer', function ($q) use ($vendorId) {
            $q->where('seller_id', $vendorId);
        });

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['marketplace'])) {
            $query->where('marketplace', $filters['marketplace']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->with(['items.product', 'items.offer.warehouse', 'customer'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Confirm order (seller accepts)
     */
    public function confirmOrder(VendorOrder $order, int $vendorId): VendorOrder
    {
        return DB::transaction(function () use ($order, $vendorId) {
            // Verify seller owns items in this order
            $hasSellerItems = $order->items()->whereHas('offer', function ($q) use ($vendorId) {
                $q->where('seller_id', $vendorId);
            })->exists();

            if (!$hasSellerItems) {
                throw ValidationException::withMessages([
                    'order' => 'You do not have items in this order.',
                ]);
            }

            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw ValidationException::withMessages([
                    'order' => 'Order cannot be confirmed in current status: ' . $order->status,
                ]);
            }

            $order->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by_seller' => true,
            ]);

            // Reserve stock for each item
            foreach ($order->items as $item) {
                if ($item->offer && $item->offer->seller_id === $vendorId) {
                    $this->reserveStock($item);
                }
            }

            event(new \App\Events\OrderConfirmed($order));

            return $order->fresh();
        });
    }

    /**
     * Reject order (seller declines)
     */
    public function rejectOrder(VendorOrder $order, int $vendorId, string $reason): VendorOrder
    {
        return DB::transaction(function () use ($order, $vendorId, $reason) {
            $hasSellerItems = $order->items()->whereHas('offer', function ($q) use ($vendorId) {
                $q->where('seller_id', $vendorId);
            })->exists();

            if (!$hasSellerItems) {
                throw ValidationException::withMessages([
                    'order' => 'You do not have items in this order.',
                ]);
            }

            if ($order->status !== 'pending') {
                throw ValidationException::withMessages([
                    'order' => 'Only pending orders can be rejected.',
                ]);
            }

            $order->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);

            event(new \App\Events\OrderRejected($order));

            return $order->fresh();
        });
    }

    /**
     * Prepare order for shipment
     */
    public function prepareForShipment(VendorOrder $order, int $vendorId): VendorOrder
    {
        return DB::transaction(function () use ($order, $vendorId) {
            if ($order->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'order' => 'Order must be confirmed before preparing for shipment.',
                ]);
            }

            $order->update([
                'status' => 'processing',
                'processing_started_at' => now(),
            ]);

            event(new \App\Events\OrderProcessingStarted($order));

            return $order->fresh();
        });
    }

    /**
     * Create shipment for order
     */
    public function createShipment(VendorOrder $order, int $vendorId, array $data): SellerShipment
    {
        return DB::transaction(function () use ($order, $vendorId, $data) {
            // Verify seller has items in order
            $sellerItems = $order->items()->whereHas('offer', function ($q) use ($vendorId) {
                $q->where('seller_id', $vendorId);
            })->get();

            if ($sellerItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => 'No items from this seller in the order.',
                ]);
            }

            // Validate warehouse
            $warehouseId = $data['warehouse_id'];
            $warehouse = \App\Models\VendorWarehouse::where('id', $warehouseId)
                ->where('vendor_id', $vendorId)
                ->firstOrFail();

            if (!$warehouse->is_active || !$warehouse->is_verified) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Invalid warehouse for shipment.',
                ]);
            }

            // Create shipment
            $shipment = SellerShipment::create([
                'vendor_id' => $vendorId,
                'order_id' => $order->id,
                'warehouse_id' => $warehouseId,
                'carrier' => $data['carrier'],
                'tracking_number' => $data['tracking_number'] ?? null,
                'service_type' => $data['service_type'] ?? null,
                'package_count' => $data['package_count'] ?? 1,
                'total_weight' => $data['total_weight'] ?? null,
                'weight_unit' => $data['weight_unit'] ?? 'kg',
                'dimensions' => $data['dimensions'] ?? null,
                'shipping_label_url' => $data['shipping_label_url'] ?? null,
                'commercial_invoice_url' => $data['commercial_invoice_url'] ?? null,
                'status' => 'label_created',
                'shipped_at' => null,
            ]);

            // Add shipment items
            foreach ($sellerItems as $item) {
                $shipment->items()->create([
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }

            event(new \App\Events\ShipmentCreated($shipment));

            return $shipment->fresh();
        });
    }

    /**
     * Mark shipment as shipped
     */
    public function ship(SellerShipment $shipment): SellerShipment
    {
        return DB::transaction(function () use ($shipment) {
            if (!in_array($shipment->status, ['label_created', 'ready_to_ship'])) {
                throw ValidationException::withMessages([
                    'shipment' => 'Shipment cannot be shipped in current status.',
                ]);
            }

            if (!$shipment->tracking_number) {
                throw ValidationException::withMessages([
                    'tracking_number' => 'Tracking number is required before shipping.',
                ]);
            }

            $shipment->update([
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            // Update order status if all items are shipped
            $order = $shipment->order;
            $allShipmentsShipped = $order->shipments()->where('status', '!=', 'shipped')->count() === 0;

            if ($allShipmentsShipped) {
                $order->update([
                    'status' => 'shipped',
                    'shipped_at' => now(),
                ]);
            }

            // Deduct inventory
            foreach ($shipment->items as $shipmentItem) {
                $this->fulfillOrderItem($shipmentItem);
            }

            event(new \App\Events\ShipmentShipped($shipment));

            return $shipment->fresh();
        });
    }

    /**
     * Reserve stock for order item
     */
    protected function reserveStock(VendorOrderItem $item): void
    {
        if (!$item->offer || !$item->offer->warehouse) {
            return;
        }

        SellerInventoryMovement::create([
            'seller_id' => $item->offer->seller_id,
            'product_id' => $item->product_id,
            'offer_id' => $item->offer->id,
            'warehouse_id' => $item->offer->warehouse_id,
            'movement_type' => 'reservation',
            'quantity' => -$item->quantity,
            'running_balance' => 0, // Will be calculated by observer or manually
            'reference_type' => VendorOrderItem::class,
            'reference_id' => $item->id,
            'notes' => "Reserved for order #{$item->order_id}",
        ]);

        // Update offer reserved quantity
        $item->offer->increment('reserved_quantity', $item->quantity);
    }

    /**
     * Fulfill order item (deduct stock)
     */
    protected function fulfillOrderItem($shipmentItem): void
    {
        $orderItem = VendorOrderItem::find($shipmentItem->order_item_id);
        if (!$orderItem || !$orderItem->offer) {
            return;
        }

        SellerInventoryMovement::create([
            'seller_id' => $orderItem->offer->seller_id,
            'product_id' => $orderItem->product_id,
            'offer_id' => $orderItem->offer->id,
            'warehouse_id' => $orderItem->offer->warehouse_id,
            'movement_type' => 'fulfillment',
            'quantity' => -$orderItem->quantity,
            'running_balance' => 0,
            'reference_type' => VendorOrderItem::class,
            'reference_id' => $orderItem->id,
            'notes' => "Fulfilled for order #{$orderItem->order_id}",
        ]);

        // Release reservation and deduct available
        $orderItem->offer->decrement('reserved_quantity', $orderItem->quantity);
        $orderItem->offer->decrement('available_quantity', $orderItem->quantity);
    }

    /**
     * Cancel order
     */
    public function cancelOrder(VendorOrder $order, int $vendorId, ?string $reason = null): VendorOrder
    {
        return DB::transaction(function () use ($order, $vendorId, $reason) {
            if (!in_array($order->status, ['pending', 'confirmed', 'processing'])) {
                throw ValidationException::withMessages([
                    'order' => 'Order cannot be cancelled in current status: ' . $order->status,
                ]);
            }

            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'cancelled_by' => $vendorId,
                'cancelled_by_type' => 'seller',
            ]);

            // Release reserved stock
            foreach ($order->items as $item) {
                if ($item->offer && $item->offer->seller_id === $vendorId) {
                    SellerInventoryMovement::create([
                        'seller_id' => $item->offer->seller_id,
                        'product_id' => $item->product_id,
                        'offer_id' => $item->offer->id,
                        'warehouse_id' => $item->offer->warehouse_id,
                        'movement_type' => 'reservation_release',
                        'quantity' => $item->quantity,
                        'running_balance' => 0,
                        'reference_type' => VendorOrderItem::class,
                        'reference_id' => $item->id,
                        'notes' => "Reservation released - order cancelled #{$order->id}",
                    ]);

                    $item->offer->decrement('reserved_quantity', $item->quantity);
                }
            }

            event(new \App\Events\OrderCancelled($order));

            return $order->fresh();
        });
    }

    /**
     * Get order statistics for seller
     */
    public function getSellerStats(int $vendorId, ?string $period = '30_days'): array
    {
        $dateFrom = now()->subDays(30);
        if ($period === '7_days') $dateFrom = now()->subDays(7);
        if ($period === '90_days') $dateFrom = now()->subDays(90);

        $ordersQuery = VendorOrder::whereHas('items.offer', function ($q) use ($vendorId) {
            $q->where('seller_id', $vendorId);
        })->where('created_at', '>=', $dateFrom);

        $stats = [
            'total_orders' => $ordersQuery->count(),
            'pending_orders' => (clone $ordersQuery)->where('status', 'pending')->count(),
            'confirmed_orders' => (clone $ordersQuery)->where('status', 'confirmed')->count(),
            'processing_orders' => (clone $ordersQuery)->where('status', 'processing')->count(),
            'shipped_orders' => (clone $ordersQuery)->where('status', 'shipped')->count(),
            'delivered_orders' => (clone $ordersQuery)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $ordersQuery)->where('status', 'cancelled')->count(),
            'overdue_orders' => (clone $ordersQuery)
                ->whereIn('status', ['confirmed', 'processing'])
                ->where('dispatch_deadline', '<', now())
                ->count(),
        ];

        return $stats;
    }
}
