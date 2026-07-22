<?php

namespace App\Services\Inventory;

use App\Models\Inventory\{StockCount, StockCountItem};
use App\Models\Marketplace\{Warehouse, InventoryStock, Product, InventoryMovement};
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stock Count Service
 * 
 * Manages physical inventory counting and adjustments
 */
class StockCountService
{
    /**
     * Create a new stock count session
     */
    public function createStockCount(array $data, int $userId): StockCount
    {
        return DB::transaction(function () use ($data, $userId) {
            $countNumber = 'SC-' . now()->format('Ymd') . '-' . str_pad(
                (StockCount::whereDate('created_at', today())->count() + 1),
                4,
                '0',
                STR_PAD_LEFT
            );

            $stockCount = StockCount::create([
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_zone_id' => $data['warehouse_zone_id'] ?? null,
                'created_by' => $userId,
                'count_number' => $countNumber,
                'count_type' => $data['count_type'] ?? StockCount::TYPE_SCHEDULED,
                'status' => StockCount::STATUS_DRAFT,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'scope' => $data['scope'] ?? null, // Which products/bins to count
            ]);

            // Generate count items based on scope
            $this->generateCountItems($stockCount);

            Log::info('Stock count created', [
                'count_id' => $stockCount->id,
                'count_number' => $countNumber,
                'warehouse_id' => $data['warehouse_id'],
            ]);

            return $stockCount;
        });
    }

    /**
     * Generate items to be counted
     */
    private function generateCountItems(StockCount $stockCount): void
    {
        $warehouseId = $stockCount->warehouse_id;
        $zoneId = $stockCount->warehouse_zone_id;
        $scope = $stockCount->scope;

        $query = InventoryStock::where('warehouse_id', $warehouseId)
            ->with(['product', 'variant', 'bin']);

        if ($zoneId) {
            $query->whereHas('bin', function ($q) use ($zoneId) {
                $q->whereHas('shelf.rack.aisle.zone', function ($q2) use ($zoneId) {
                    $q2->where('warehouse_zone_id', $zoneId);
                });
            });
        }

        // Filter by specific products if in scope
        if (isset($scope['product_ids'])) {
            $query->whereIn('product_id', $scope['product_ids']);
        }

        // Filter by specific bins if in scope
        if (isset($scope['bin_ids'])) {
            $query->whereIn('warehouse_bin_id', $scope['bin_ids']);
        }

        $stocks = $query->get();

        foreach ($stocks as $stock) {
            StockCountItem::create([
                'stock_count_id' => $stockCount->id,
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'inventory_stock_id' => $stock->id,
                'warehouse_bin_id' => $stock->warehouse_bin_id,
                'inventory_batch_id' => $stock->inventory_batch_id,
                'expected_quantity' => $stock->quantity_on_hand,
                'counted_quantity' => 0,
                'variance_quantity' => 0,
                'unit_cost' => $stock->unit_cost,
                'variance_value' => 0,
                'status' => StockCountItem::STATUS_PENDING,
                'requires_review' => false,
            ]);
        }

        // Update totals
        $stockCount->update([
            'total_items_expected' => $stocks->count(),
        ]);
    }

    /**
     * Start a stock count
     */
    public function startStockCount(int $countId): StockCount
    {
        $stockCount = StockCount::findOrFail($countId);
        
        if ($stockCount->status !== StockCount::STATUS_DRAFT) {
            throw new \Exception('Only draft counts can be started');
        }

        $stockCount->start();

        Log::info('Stock count started', ['count_id' => $countId]);

        return $stockCount;
    }

    /**
     * Record a count for an item
     */
    public function recordCount(int $itemId, float $quantity, int $userId, ?string $notes = null): StockCountItem
    {
        return DB::transaction(function () use ($itemId, $quantity, $userId, $notes) {
            $item = StockCountItem::findOrFail($itemId);
            $item->recordCount($quantity, $userId, $notes);

            // Update parent count totals
            $this->updateCountTotals($item->stock_count_id);

            return $item;
        });
    }

    /**
     * Review a counted item
     */
    public function reviewCount(int $itemId, int $userId, bool $approved, ?string $notes = null): StockCountItem
    {
        $item = StockCountItem::findOrFail($itemId);
        $item->review($userId, $approved, $notes);

        if ($approved && $item->variance_quantity != 0) {
            $item->update(['status' => StockCountItem::STATUS_REVIEWED]);
        }

        return $item;
    }

    /**
     * Complete counting phase
     */
    public function completeCounting(int $countId): StockCount
    {
        $stockCount = StockCount::findOrFail($countId);
        
        if ($stockCount->status !== StockCount::STATUS_IN_PROGRESS) {
            throw new \Exception('Only in-progress counts can be completed');
        }

        // Verify all items are counted
        $pendingItems = $stockCount->items()->where('status', StockCountItem::STATUS_PENDING)->count();
        if ($pendingItems > 0) {
            throw new \Exception("{$pendingItems} items still pending count");
        }

        $stockCount->markCountingComplete();

        Log::info('Stock count counting completed', ['count_id' => $countId]);

        return $stockCount;
    }

    /**
     * Approve and post adjustments
     */
    public function approveAndPost(int $countId, int $approverId): StockCount
    {
        return DB::transaction(function () use ($countId, $approverId) {
            $stockCount = StockCount::findOrFail($countId);
            
            if ($stockCount->status !== StockCount::STATUS_COUNTING_COMPLETE) {
                throw new \Exception('Count must be completed before approval');
            }

            $stockCount->approve($approverId);

            // Post adjustments for each item with variance
            $itemsWithVariance = $stockCount->items()
                ->where('variance_quantity', '!=', 0)
                ->get();

            foreach ($itemsWithVariance as $item) {
                $this->postAdjustment($stockCount, $item, $approverId);
            }

            $stockCount->postAdjustments();

            Log::info('Stock count approved and posted', [
                'count_id' => $countId,
                'adjustments_count' => $itemsWithVariance->count(),
            ]);

            return $stockCount;
        });
    }

    /**
     * Post adjustment for a single item
     */
    private function postAdjustment(StockCount $stockCount, StockCountItem $item, int $userId): void
    {
        $stock = $item->inventoryStock;
        
        if (!$stock) {
            return;
        }

        // Create inventory movement for the adjustment
        InventoryMovement::create([
            'product_id' => $stock->product_id,
            'product_variant_id' => $stock->product_variant_id,
            'warehouse_id' => $stock->warehouse_id,
            'inventory_stock_id' => $stock->id,
            'movement_type' => $item->variance_quantity > 0 ? 'adjustment_in' : 'adjustment_out',
            'quantity' => abs($item->variance_quantity),
            'unit_cost' => $item->unit_cost,
            'total_value' => abs($item->variance_value),
            'reference_type' => StockCount::class,
            'reference_id' => $stockCount->id,
            'stock_count_id' => $stockCount->id,
            'reason' => 'Physical inventory adjustment: ' . ($item->variance_reason ?? 'Stock count variance'),
            'notes' => $item->adjustment_notes,
            'created_by' => $userId,
        ]);

        // Update stock quantity
        if ($item->variance_quantity > 0) {
            $stock->increment('quantity_on_hand', $item->variance_quantity);
        } else {
            $stock->decrement('quantity_on_hand', abs($item->variance_quantity));
        }

        $item->update(['status' => StockCountItem::STATUS_ADJUSTED]);
    }

    /**
     * Update count totals
     */
    private function updateCountTotals(int $countId): void
    {
        $stockCount = StockCount::findOrFail($countId);
        
        $totals = $stockCount->items()
            ->selectRaw('
                COUNT(*) as total_counted,
                SUM(CASE WHEN variance_quantity = 0 THEN 1 ELSE 0 END) as matched,
                SUM(CASE WHEN variance_quantity != 0 THEN 1 ELSE 0 END) as variance,
                SUM(variance_value) as total_variance_value
            ')
            ->where('status', '!=', StockCountItem::STATUS_PENDING)
            ->first();

        $stockCount->update([
            'total_items_counted' => $totals->total_counted ?? 0,
            'total_items_matched' => $totals->matched ?? 0,
            'total_items_variance' => $totals->variance ?? 0,
            'variance_value' => $totals->total_variance_value ?? 0,
        ]);
    }

    /**
     * Get count summary
     */
    public function getCountSummary(int $countId): array
    {
        $stockCount = StockCount::with(['items', 'warehouse'])->findOrFail($countId);
        
        return [
            'count_number' => $stockCount->count_number,
            'warehouse' => $stockCount->warehouse->name,
            'type' => $stockCount->count_type,
            'status' => $stockCount->status,
            'progress' => [
                'expected' => $stockCount->total_items_expected,
                'counted' => $stockCount->total_items_counted,
                'matched' => $stockCount->total_items_matched,
                'variance' => $stockCount->total_items_variance,
                'match_rate' => $stockCount->match_rate,
            ],
            'variance_value' => $stockCount->variance_value,
            'scheduled_date' => $stockCount->scheduled_date,
            'started_at' => $stockCount->started_at,
            'completed_at' => $stockCount->completed_at,
        ];
    }
}
