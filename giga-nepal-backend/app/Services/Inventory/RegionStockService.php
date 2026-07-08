<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegionStockService
{
    public function publicStock(int $productId, array $filters = []): array
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return ['status' => 'unknown', 'items' => [], 'summary' => $this->emptySummary()];
        }

        $query = DB::table('inventory_stocks')->where('product_id', $productId);
        foreach (['marketplace_id', 'country_id', 'region_id', 'city_id'] as $column) {
            if (isset($filters[$column]) && Schema::hasColumn('inventory_stocks', $column)) {
                $query->where($column, $filters[$column]);
            }
        }
        if (Schema::hasColumn('inventory_stocks', 'is_active')) {
            $query->where('is_active', true);
        }
        if (Schema::hasColumn('inventory_stocks', 'status')) {
            $query->where('status', 'active');
        }

        $rows = $query->get();
        $available = (float) $rows->sum(fn ($row) => (float) ($row->quantity_available ?? $row->available_quantity ?? 0));
        $reserved = (float) $rows->sum(fn ($row) => (float) ($row->quantity_reserved ?? $row->reserved_quantity ?? 0));
        $incoming = (float) $rows->sum(fn ($row) => (float) ($row->quantity_incoming ?? $row->incoming_quantity ?? 0));
        $quoteOnly = $rows->contains(fn ($row) => (bool) ($row->quote_only ?? false));
        $backorder = $rows->contains(fn ($row) => (bool) ($row->backorder_allowed ?? false));

        return [
            'status' => $available > 0 ? 'in_stock' : ($quoteOnly ? 'quote_only' : ($backorder ? 'backorder_available' : 'out_of_stock')),
            'summary' => [
                'available_quantity' => $available,
                'reserved_quantity' => $reserved,
                'incoming_quantity' => $incoming,
                'quote_only' => $quoteOnly,
                'backorder_allowed' => $backorder,
            ],
            'items' => $rows->map(fn ($row) => [
                'marketplace_id' => $row->marketplace_id ?? null,
                'country_id' => $row->country_id ?? null,
                'region_id' => $row->region_id ?? null,
                'city_id' => $row->city_id ?? null,
                'available_quantity' => (float) ($row->quantity_available ?? $row->available_quantity ?? 0),
                'incoming_quantity' => (float) ($row->quantity_incoming ?? $row->incoming_quantity ?? 0),
                'quote_only' => (bool) ($row->quote_only ?? false),
                'backorder_allowed' => (bool) ($row->backorder_allowed ?? false),
            ])->values()->all(),
        ];
    }

    private function emptySummary(): array
    {
        return ['available_quantity' => 0, 'reserved_quantity' => 0, 'incoming_quantity' => 0, 'quote_only' => false, 'backorder_allowed' => false];
    }
}
