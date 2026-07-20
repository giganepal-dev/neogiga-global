<?php

namespace App\Services\Manufacturer;

use App\Models\Manufacturer;
use App\Models\ManufacturerGlobalInventory;
use App\Models\ManufacturerRegionalAllocation;
use App\Services\Inventory\StockMovementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManufacturerInventoryService
{
    public function __construct(private readonly StockMovementService $stockMovements) {}

    public function globalSummary(Manufacturer $manufacturer): array
    {
        if (! Schema::hasTable('manufacturer_global_inventory')) {
            return [
                'sku_count' => 0,
                'quantity_on_hand' => 0.0,
                'quantity_reserved' => 0.0,
                'quantity_available' => 0.0,
            ];
        }

        $rows = DB::table('manufacturer_global_inventory')
            ->where('manufacturer_id', $manufacturer->id)
            ->selectRaw('count(*) as sku_count, sum(quantity_on_hand) as on_hand, sum(quantity_reserved) as reserved')
            ->first();

        $onHand = (float) ($rows->on_hand ?? 0);
        $reserved = (float) ($rows->reserved ?? 0);

        return [
            'sku_count' => (int) ($rows->sku_count ?? 0),
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => $reserved,
            'quantity_available' => max(0, $onHand - $reserved),
        ];
    }

    public function paginateGlobalInventory(Manufacturer $manufacturer, int $perPage = 20): LengthAwarePaginator
    {
        if (! Schema::hasTable('manufacturer_global_inventory')) {
            return new Paginator([], 0, $perPage);
        }

        return DB::table('manufacturer_global_inventory as gi')
            ->join('products as p', 'p.id', '=', 'gi.product_id')
            ->where('gi.manufacturer_id', $manufacturer->id)
            ->select([
                'gi.*',
                'p.name as product_name',
                'p.sku as product_sku',
                'p.status as product_status',
            ])
            ->orderByDesc('gi.updated_at')
            ->paginate($perPage);
    }

    public function paginateAllocations(Manufacturer $manufacturer, int $perPage = 20): LengthAwarePaginator
    {
        if (! Schema::hasTable('manufacturer_regional_allocations')) {
            return new Paginator([], 0, $perPage);
        }

        return DB::table('manufacturer_regional_allocations as a')
            ->join('products as p', 'p.id', '=', 'a.product_id')
            ->where('a.manufacturer_id', $manufacturer->id)
            ->select([
                'a.*',
                'p.name as product_name',
                'p.sku as product_sku',
            ])
            ->orderByDesc('a.id')
            ->paginate($perPage);
    }

    public function allocationSummary(Manufacturer $manufacturer): array
    {
        if (! Schema::hasTable('manufacturer_regional_allocations')) {
            return ['total' => 0, 'by_status' => collect()];
        }

        return [
            'total' => DB::table('manufacturer_regional_allocations')->where('manufacturer_id', $manufacturer->id)->count(),
            'by_status' => DB::table('manufacturer_regional_allocations')
                ->select('status', DB::raw('count(*) as total'))
                ->where('manufacturer_id', $manufacturer->id)
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
        ];
    }

    public function syncFromCatalog(Manufacturer $manufacturer): int
    {
        if (! Schema::hasTable('manufacturer_global_inventory') || ! Schema::hasTable('products')) {
            return 0;
        }

        $products = DB::table('products')
            ->where('manufacturer_id', $manufacturer->id)
            ->get(['id', 'sku']);

        $synced = 0;
        foreach ($products as $product) {
            ManufacturerGlobalInventory::updateOrCreate(
                [
                    'manufacturer_id' => $manufacturer->id,
                    'product_id' => $product->id,
                ],
                [
                    'sku' => $product->sku,
                ]
            );
            $synced++;
        }

        return $synced;
    }

    public function allocateToRegion(Manufacturer $manufacturer, array $data): ManufacturerRegionalAllocation
    {
        abort_unless(
            DB::table('products')->where('id', $data['product_id'])->where('manufacturer_id', $manufacturer->id)->exists(),
            422,
            'Product does not belong to this manufacturer.'
        );

        return DB::transaction(function () use ($manufacturer, $data) {
            $inventory = ManufacturerGlobalInventory::query()
                ->where('manufacturer_id', $manufacturer->id)
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            $qty = (float) $data['quantity_allocated'];
            if ($inventory) {
                $available = (float) $inventory->quantity_on_hand - (float) $inventory->quantity_reserved;
                abort_if($qty > $available, 422, 'Allocation exceeds available global inventory.');
            }

            $status = $data['status'] ?? 'pending';
            $warehouseId = $data['warehouse_id'] ?? null;

            $allocation = ManufacturerRegionalAllocation::create([
                'manufacturer_id' => $manufacturer->id,
                'marketplace_id' => $data['marketplace_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'product_id' => $data['product_id'],
                'quantity_allocated' => $qty,
                'status' => $warehouseId ? 'delivered' : $status,
                'notes' => $data['notes'] ?? null,
                'allocated_at' => now(),
            ]);

            if ($warehouseId && Schema::hasTable('inventory_stocks')) {
                if ($inventory) {
                    $inventory->forceFill([
                        'quantity_on_hand' => max(0, (float) $inventory->quantity_on_hand - $qty),
                    ])->save();
                }

                $this->stockMovements->adjust([
                    'product_id' => (int) $data['product_id'],
                    'warehouse_id' => (int) $warehouseId,
                    'marketplace_id' => $data['marketplace_id'] ?? null,
                    'quantity_change' => (int) ceil($qty),
                    'movement_type' => 'manufacturer_allocation',
                    'reference_type' => 'manufacturer_regional_allocation',
                    'reference_id' => $allocation->id,
                    'notes' => 'Manufacturer regional allocation #'.$allocation->id,
                ]);
            } elseif ($inventory) {
                $inventory->forceFill([
                    'quantity_reserved' => (float) $inventory->quantity_reserved + $qty,
                ])->save();
            }

            return $allocation->fresh();
        });
    }

    public function marketplacesForSelect(): Collection
    {
        if (! Schema::hasTable('marketplaces')) {
            return collect();
        }

        return DB::table('marketplaces')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function warehousesForSelect(): Collection
    {
        if (! Schema::hasTable('warehouses')) {
            return collect();
        }

        return DB::table('warehouses')->orderBy('name')->get(['id', 'name', 'code']);
    }
}
