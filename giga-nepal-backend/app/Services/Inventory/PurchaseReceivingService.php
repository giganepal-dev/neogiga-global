<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class PurchaseReceivingService
{
    public function __construct(private StockMovementService $stockMovements)
    {
    }

    public function receive(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $poId = $data['purchase_order_id'] ?? null;
            $received = [];
            foreach ($data['items'] as $item) {
                $received[] = $this->stockMovements->adjust([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity_change' => (int) $item['quantity'],
                    'movement_type' => 'purchase_receive',
                    'reference_type' => 'inventory_purchase_order',
                    'reference_id' => $poId,
                    'unit_cost' => $item['unit_cost'] ?? null,
                    'notes' => $data['notes'] ?? 'Purchase receiving',
                ]);
            }

            if ($poId) {
                DB::table('inventory_purchase_orders')->where('id', $poId)->update(['status' => 'received', 'received_at' => now(), 'updated_at' => now()]);
            }

            return ['received_count' => count($received), 'items' => $received];
        });
    }
}
