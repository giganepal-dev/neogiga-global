<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StockMovementService
{
    public function adjust(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $stock = $this->stock($data);
            $change = (int) $data['quantity_change'];
            $before = (int) $stock->quantity_available;
            $after = $before + $change;

            if ($after < 0 && !($data['allow_negative'] ?? false)) {
                throw new RuntimeException('Insufficient available stock.');
            }

            DB::table('inventory_stocks')->where('id', $stock->id)->update([
                'quantity_available' => $after,
                'quantity_on_hand' => max(0, $after + (int) $stock->quantity_reserved + (int) $stock->quantity_damaged),
                'last_movement_at' => now(),
                'updated_at' => now(),
            ]);

            $movementId = DB::table('inventory_movements')->insertGetId([
                'inventory_stock_id' => $stock->id,
                'product_id' => $stock->product_id,
                'variant_id' => $stock->variant_id,
                'warehouse_id' => $stock->warehouse_id,
                'marketplace_id' => $stock->marketplace_id,
                'vendor_id' => $stock->vendor_id,
                'movement_type' => $data['movement_type'] ?? ($change >= 0 ? 'in' : 'out'),
                'quantity_change' => $change,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'metadata' => json_encode($data['metadata'] ?? []),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['stock_id' => $stock->id, 'movement_id' => $movementId, 'quantity_before' => $before, 'quantity_after' => $after];
        });
    }

    public function availability(int $productId, ?int $warehouseId = null): array
    {
        $query = DB::table('inventory_stocks')->where('product_id', $productId)->where('is_active', true);
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $rows = $query->get();

        return [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'available' => (int) $rows->sum('quantity_available'),
            'reserved' => (int) $rows->sum('quantity_reserved'),
            'locations' => $rows,
        ];
    }

    public function stock(array $data): object
    {
        $query = DB::table('inventory_stocks')
            ->where('product_id', (int) $data['product_id'])
            ->where('warehouse_id', (int) $data['warehouse_id'])
            ->where('variant_id', $data['variant_id'] ?? null);

        $stock = $query->lockForUpdate()->first();
        if ($stock) {
            return $stock;
        }

        $product = DB::table('products')->where('id', (int) $data['product_id'])->first();
        $id = DB::table('inventory_stocks')->insertGetId([
            'product_id' => (int) $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'warehouse_id' => (int) $data['warehouse_id'],
            'vendor_id' => $data['vendor_id'] ?? $product?->vendor_id,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'sku' => $data['sku'] ?? $product?->sku ?? 'SKU-'.Str::upper(Str::random(8)),
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'quantity_incoming' => 0,
            'quantity_on_hand' => 0,
            'reorder_point' => $data['reorder_point'] ?? 10,
            'is_active' => true,
            'metadata' => json_encode(['created_by' => 'stock_movement_service']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('inventory_stocks')->where('id', $id)->lockForUpdate()->first();
    }
}
