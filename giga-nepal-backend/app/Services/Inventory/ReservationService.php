<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReservationService
{
    public function __construct(private StockMovementService $stockMovements)
    {
    }

    public function reserve(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $stock = $this->stockMovements->stock($data);
            $qty = (int) $data['quantity'];

            if ((int) $stock->quantity_available < $qty) {
                throw new RuntimeException('Requested quantity is not available.');
            }

            DB::table('inventory_stocks')->where('id', $stock->id)->update([
                'quantity_available' => (int) $stock->quantity_available - $qty,
                'quantity_reserved' => (int) $stock->quantity_reserved + $qty,
                'updated_at' => now(),
            ]);

            $reservationId = DB::table('reserved_stocks')->insertGetId([
                'inventory_stock_id' => $stock->id,
                'warehouse_id' => $stock->warehouse_id,
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->variant_id,
                'reference_type' => $data['reference_type'] ?? 'api',
                'reference_id' => $data['reference_id'] ?? null,
                'quantity' => $qty,
                'expires_at' => now()->addMinutes((int) ($data['ttl_minutes'] ?? 30)),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_movements')->insert([
                'inventory_stock_id' => $stock->id,
                'product_id' => $stock->product_id,
                'variant_id' => $stock->variant_id,
                'warehouse_id' => $stock->warehouse_id,
                'marketplace_id' => $stock->marketplace_id,
                'vendor_id' => $stock->vendor_id,
                'movement_type' => 'reserve',
                'quantity_change' => -$qty,
                'quantity_before' => (int) $stock->quantity_available,
                'quantity_after' => (int) $stock->quantity_available - $qty,
                'reference_type' => 'reserved_stock',
                'reference_id' => $reservationId,
                'notes' => $data['notes'] ?? null,
                'metadata' => json_encode([]),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['reservation_id' => $reservationId, 'stock_id' => $stock->id, 'quantity' => $qty];
        });
    }

    public function release(int $reservationId, string $reason = 'manual_release'): array
    {
        return DB::transaction(function () use ($reservationId, $reason) {
            $reservation = DB::table('reserved_stocks')->where('id', $reservationId)->lockForUpdate()->first();
            if (!$reservation || $reservation->status !== 'active') {
                return ['released' => false, 'reason' => 'Reservation not active.'];
            }

            $stock = DB::table('inventory_stocks')->where('id', $reservation->inventory_stock_id)->lockForUpdate()->first();
            DB::table('inventory_stocks')->where('id', $stock->id)->update([
                'quantity_available' => (int) $stock->quantity_available + (int) $reservation->quantity,
                'quantity_reserved' => max(0, (int) $stock->quantity_reserved - (int) $reservation->quantity),
                'updated_at' => now(),
            ]);

            DB::table('reserved_stocks')->where('id', $reservationId)->update([
                'status' => 'released',
                'released_at' => now(),
                'notes' => trim(($reservation->notes ?? '').' '.$reason),
                'updated_at' => now(),
            ]);

            return ['released' => true, 'reservation_id' => $reservationId];
        });
    }

    public function useReservation(int $reservationId): array
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = DB::table('reserved_stocks')->where('id', $reservationId)->lockForUpdate()->first();
            if (!$reservation || $reservation->status !== 'active') {
                return ['used' => false, 'reason' => 'Reservation not active.'];
            }

            $stock = DB::table('inventory_stocks')->where('id', $reservation->inventory_stock_id)->lockForUpdate()->first();
            DB::table('inventory_stocks')->where('id', $stock->id)->update([
                'quantity_reserved' => max(0, (int) $stock->quantity_reserved - (int) $reservation->quantity),
                'updated_at' => now(),
            ]);

            DB::table('reserved_stocks')->where('id', $reservationId)->update(['status' => 'used', 'used_at' => now(), 'updated_at' => now()]);

            return ['used' => true, 'reservation_id' => $reservationId];
        });
    }
}
