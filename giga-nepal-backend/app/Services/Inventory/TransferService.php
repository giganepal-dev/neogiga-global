<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(private StockMovementService $stockMovements)
    {
    }

    public function transfer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $out = $this->stockMovements->adjust([
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'warehouse_id' => $data['from_warehouse_id'],
                'quantity_change' => -abs((int) $data['quantity']),
                'movement_type' => 'transfer_out',
                'reference_type' => 'inventory_transfer',
                'notes' => $data['notes'] ?? null,
            ]);
            $in = $this->stockMovements->adjust([
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'warehouse_id' => $data['to_warehouse_id'],
                'quantity_change' => abs((int) $data['quantity']),
                'movement_type' => 'transfer_in',
                'reference_type' => 'inventory_transfer',
                'notes' => $data['notes'] ?? null,
            ]);

            return ['out' => $out, 'in' => $in];
        });
    }
}
