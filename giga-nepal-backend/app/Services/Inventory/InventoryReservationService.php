<?php

namespace App\Services\Inventory;

use App\Models\Marketplace\Cart;
use App\Models\Marketplace\CartItem;
use App\Models\Marketplace\CartReservation;
use App\Models\Marketplace\InventoryStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryReservationService
{
    /**
     * Reserve inventory for cart items (15-minute soft reservation)
     * 
     * This method creates temporary reservations for all items in a cart,
     * preventing overselling by reducing available quantity.
     * 
     * @param Cart $cart
     * @return array ['success' => bool, 'message' => string, 'reservations' => Collection]
     */
    public function reserveCartInventory(Cart $cart): array
    {
        return DB::transaction(function () use ($cart) {
            $cart->load(['items.product', 'items.variant']);
            
            $reservations = [];
            $failedItems = [];

            foreach ($cart->items as $item) {
                $reservationResult = $this->reserveItem($item);
                
                if ($reservationResult['success']) {
                    $reservations[] = $reservationResult['reservation'];
                } else {
                    $failedItems[] = [
                        'item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'reason' => $reservationResult['reason'],
                    ];
                }
            }

            if (!empty($failedItems)) {
                // Rollback any successful reservations if any item failed
                foreach ($reservations as $reservation) {
                    $reservation->release('Partial reservation failure - rollback');
                }

                return [
                    'success' => false,
                    'message' => 'Insufficient inventory for some items',
                    'failed_items' => $failedItems,
                    'reservations' => collect(),
                ];
            }

            return [
                'success' => true,
                'message' => 'Inventory reserved successfully for 15 minutes',
                'reservations' => collect($reservations),
                'expires_at' => $reservations[0]->expires_at ?? null,
            ];
        });
    }

    /**
     * Reserve inventory for a single cart item
     * 
     * @param CartItem $item
     * @return array ['success' => bool, 'reason' => string|null, 'reservation' => CartReservation|null]
     */
    protected function reserveItem(CartItem $item): array
    {
        $variantId = $item->variant_id;
        $productId = $item->product_id;
        $quantity = $item->quantity;

        // Find available stock
        $stockQuery = InventoryStock::query()
            ->where('product_id', $productId)
            ->where('is_active', true);

        if ($variantId) {
            $stockQuery->where('variant_id', $variantId);
        }

        // Get total available across all warehouses
        $totalAvailable = $stockQuery->sum('quantity_available');
        
        if ($totalAvailable < $quantity) {
            return [
                'success' => false,
                'reason' => "Only {$totalAvailable} units available, requested {$quantity}",
                'reservation' => null,
            ];
        }

        // Distribute reservation across warehouses (simple proportional allocation)
        $stocks = $stockQuery->get();
        $remainingToReserve = $quantity;
        $reservations = [];

        foreach ($stocks as $stock) {
            if ($remainingToReserve <= 0) {
                break;
            }

            $allocateFromThis = min($stock->quantity_available, $remainingToReserve);
            
            if ($allocateFromThis > 0) {
                // Decrement available quantity
                $stock->decrement('quantity_available', $allocateFromThis);
                $stock->increment('quantity_reserved', $allocateFromThis);

                // Create reservation record
                $reservation = CartReservation::create([
                    'cart_id' => $item->cart_id,
                    'cart_item_id' => $item->id,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'warehouse_id' => $stock->warehouse_id,
                    'marketplace_id' => $stock->marketplace_id,
                    'quantity_reserved' => $allocateFromThis,
                    'reserved_at' => now(),
                    'expires_at' => now()->addMinutes(CartReservation::RESERVATION_DURATION_MINUTES),
                    'status' => CartReservation::STATUS_ACTIVE,
                    'metadata' => [
                        'original_stock_id' => $stock->id,
                        'allocated_quantity' => $allocateFromThis,
                    ],
                ]);

                $reservations[] = $reservation;
                $remainingToReserve -= $allocateFromThis;
            }
        }

        // Return the first reservation (or combine if needed)
        return [
            'success' => true,
            'reason' => null,
            'reservation' => $reservations[0] ?? null,
            'all_reservations' => $reservations,
        ];
    }

    /**
     * Release reservations for a cart (e.g., user removes items or abandons cart)
     * 
     * @param Cart $cart
     * @param string $reason
     * @return int Number of reservations released
     */
    public function releaseCartReservations(Cart $cart, string $reason = 'Cart updated or abandoned'): int
    {
        $released = 0;

        $reservations = CartReservation::query()
            ->where('cart_id', $cart->id)
            ->where('status', CartReservation::STATUS_ACTIVE)
            ->get();

        foreach ($reservations as $reservation) {
            $this->releaseReservation($reservation, $reason);
            $released++;
        }

        return $released;
    }

    /**
     * Release a single reservation and restore inventory
     * 
     * @param CartReservation $reservation
     * @param string $reason
     * @return void
     */
    public function releaseReservation(CartReservation $reservation, string $reason = null): void
    {
        DB::transaction(function () use ($reservation, $reason) {
            // Restore inventory
            $stock = InventoryStock::find($reservation->metadata['original_stock_id'] ?? null);
            
            if ($stock) {
                $stock->increment('quantity_available', $reservation->quantity_reserved);
                $stock->decrement('quantity_reserved', $reservation->quantity_reserved);
            }

            // Mark reservation as released
            $reservation->release($reason);
        });
    }

    /**
     * Convert reservations to permanent (order placed successfully)
     * 
     * @param Cart $cart
     * @return int Number of reservations converted
     */
    public function convertCartReservations(Cart $cart): int
    {
        $converted = 0;

        $reservations = CartReservation::query()
            ->where('cart_id', $cart->id)
            ->where('status', CartReservation::STATUS_ACTIVE)
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->convert();
            $converted++;
        }

        return $converted;
    }

    /**
     * Release expired reservations (called by cron job)
     * 
     * @return array ['released_count' => int, 'restored_quantity' => int]
     */
    public function releaseExpiredReservations(): array
    {
        $expiredReservations = CartReservation::query()
            ->where('status', CartReservation::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->get();

        $releasedCount = 0;
        $restoredQuantity = 0;

        foreach ($expiredReservations as $reservation) {
            $quantity = $reservation->quantity_reserved;
            $this->releaseReservation($reservation, 'Reservation expired (15-minute timeout)');
            $releasedCount++;
            $restoredQuantity += $quantity;
        }

        Log::info("Released {$releasedCount} expired cart reservations, restored {$restoredQuantity} units to inventory");

        return [
            'released_count' => $releasedCount,
            'restored_quantity' => $restoredQuantity,
        ];
    }

    /**
     * Get active reservations for a product
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductReservations(int $productId, ?int $variantId = null)
    {
        $query = CartReservation::query()
            ->where('product_id', $productId)
            ->where('status', CartReservation::STATUS_ACTIVE);

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        return $query->get();
    }

    /**
     * Check if user has active reservations
     * 
     * @param int $userId
     * @return bool
     */
    public function userHasActiveReservations(int $userId): bool
    {
        return CartReservation::query()
            ->whereHas('cart', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('status', CartReservation::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get reservation summary for dashboard
     * 
     * @return array
     */
    public function getReservationSummary(): array
    {
        $active = CartReservation::query()
            ->where('status', CartReservation::STATUS_ACTIVE)
            ->where('expires_at', '>', now());

        return [
            'active_reservations' => $active->count(),
            'total_units_reserved' => $active->sum('quantity_reserved'),
            'expiring_soon' => (clone $active)
                ->where('expires_at', '<=', now()->addMinutes(5))
                ->count(),
            'expired_pending_release' => CartReservation::query()
                ->where('status', CartReservation::STATUS_ACTIVE)
                ->where('expires_at', '<=', now())
                ->count(),
        ];
    }
}
