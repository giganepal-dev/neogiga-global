<?php

namespace App\Services\Bom;

use App\Models\Bom\BomProject;
use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BomAvailabilityService
{
    public function forProject(BomProject $project): array
    {
        return $project->items()->publiclyAvailable()->orderBy('priority')->get()->map(function ($item) {
            return [
                'bom_project_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'available_quantity' => $this->availableQuantity((int) $item->product_id),
                'required_quantity' => (float) $item->quantity,
                'substitute_allowed' => (bool) $item->substitute_allowed,
            ];
        })->values()->all();
    }

    private function availableQuantity(int $productId): float
    {
        if ($productId <= 0
            || ! Product::published()->whereKey($productId)->exists()
            || ! Schema::hasTable('inventory_stocks')) {
            return 0.0;
        }

        $column = Schema::hasColumn('inventory_stocks', 'available_quantity') ? 'available_quantity' : 'quantity';

        if (! Schema::hasColumn('inventory_stocks', $column)) {
            return 0.0;
        }

        return (float) DB::table('inventory_stocks')->where('product_id', $productId)->sum($column);
    }
}
