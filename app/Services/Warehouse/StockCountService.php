<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\StockCount;
use App\Models\Warehouse\StockCountItem;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\InventoryBatch;
use App\Models\Warehouse\InventorySerial;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductWarehouse;
use App\Models\Marketplace\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

/**
 * Stock Count Service
 * 
 * Manages stock counting/stocktake operations including cycle counts,
 * annual inventories, and spot checks.
 */
class StockCountService
{
    /**
     * Create a new stock count
     */
    public function createStockCount(
        int $warehouseId,
        string $type = 'scheduled',
        ?int $zoneId = null,
        ?User $user = null,
        ?string $reason = null
    ): StockCount {
        return DB::transaction(function () use ($warehouseId, $type, $zoneId, $user, $reason) {
            $referenceNumber = $this->generateReferenceNumber($warehouseId, $type);

            $stockCount = StockCount::create([
                'warehouse_id' => $warehouseId,
                'zone_id' => $zoneId,
                'conducted_by' => $user?->id ?? auth()->id(),
                'reference_number' => $referenceNumber,
                'type' => $type,
                'status' => StockCount::STATUS_DRAFT,
                'reason' => $reason,
            ]);

            // Auto-populate items if zone specified
            if ($zoneId) {
                $this->populateItemsForZone($stockCount, $zoneId);
            }

            return $stockCount;
        });
    }

    /**
     * Populate stock count items for a zone
     */
    public function populateItemsForZone(StockCount $stockCount, int $zoneId): int
    {
        return DB::transaction(function () use ($stockCount, $zoneId) {
            // Get all products in the zone's bins
            $productWarehouses = ProductWarehouse::join('warehouse_bins', 'product_warehouses.bin_id', '=', 'warehouse_bins.id')
                ->join('warehouse_shelves', 'warehouse_bins.shelf_id', '=', 'warehouse_shelves.id')
                ->join('warehouse_racks', 'warehouse_shelves.rack_id', '=', 'warehouse_racks.id')
                ->join('warehouse_aisles', 'warehouse_racks.aisle_id', '=', 'warehouse_aisles.id')
                ->where('warehouse_aisles.zone_id', $zoneId)
                ->select('product_warehouses.*')
                ->get();

            $count = 0;
            foreach ($productWarehouses as $pw) {
                StockCountItem::create([
                    'stock_count_id' => $stockCount->id,
                    'product_id' => $pw->product_id,
                    'bin_id' => $pw->bin_id,
                    'system_quantity' => $pw->available_stock + $pw->reserved_stock,
                    'counted_quantity' => 0,
                    'variance_quantity' => 0,
                    'unit_cost' => null, // Will be populated from product cost
                ]);
                $count++;
            }

            return $count;
        });
    }

    /**
     * Start a stock count
     */
    public function startStockCount(StockCount $stockCount): void
    {
        $stockCount->start();
    }

    /**
     * Record a count for an item
     */
    public function recordCount(
        StockCountItem $item,
        float $quantity,
        ?User $user = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($item, $quantity, $user, $notes) {
            $item->markAsCounted($quantity, $user);
            
            if ($notes) {
                $item->update(['notes' => $notes]);
            }

            // Update parent stock count stats
            $this->updateStockCountStats($item->stockCount);
        });
    }

    /**
     * Complete a stock count and create adjustments
     */
    public function completeStockCount(StockCount $stockCount, User $approver): void
    {
        DB::transaction(function () use ($stockCount, $approver) {
            // Verify all items are counted
            $uncountedItems = $stockCount->items()
                ->whereNull('counted_at')
                ->count();

            if ($uncountedItems > 0) {
                throw new Exception("Cannot complete stock count. {$uncountedItems} items not yet counted.");
            }

            // Create inventory adjustments for variances
            $varianceItems = $stockCount->items()->withVariance()->get();
            
            foreach ($varianceItems as $item) {
                $this->createAdjustment($item, $approver);
            }

            // Update stock count status
            $stockCount->update([
                'status' => StockCount::STATUS_COMPLETED,
                'completed_at' => now(),
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Create inventory adjustment for a variance
     */
    private function createAdjustment(StockCountItem $item, User $approver): void
    {
        $variance = $item->variance_quantity;
        
        if (abs($variance) < 0.0001) {
            return;
        }

        $movementType = $variance > 0 ? 'stock_count_gain' : 'stock_count_loss';
        $quantity = abs($variance);

        $movement = InventoryMovement::create([
            'product_id' => $item->product_id,
            'warehouse_id' => $item->stockCount->warehouse_id,
            'from_warehouse_id' => $variance < 0 ? $item->stockCount->warehouse_id : null,
            'to_warehouse_id' => $variance > 0 ? $item->stockCount->warehouse_id : null,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'unit_cost' => $item->unit_cost,
            'total_value' => $quantity * ($item->unit_cost ?? 0),
            'reference_type' => StockCount::class,
            'reference_id' => $item->stockCount_id,
            'notes' => "Stock count adjustment: {$item->stockCount->reference_number}. Variance reason: {$item->variance_reason ?? 'N/A'}",
            'created_by' => $approver->id,
        ]);

        // Update product warehouse stock
        $productWarehouse = ProductWarehouse::firstOrCreate(
            ['product_id' => $item->product_id, 'warehouse_id' => $item->stockCount->warehouse_id],
            ['available_stock' => 0, 'reserved_stock' => 0]
        );

        if ($variance > 0) {
            $productWarehouse->increment('available_stock', $quantity);
        } else {
            $productWarehouse->decrement('available_stock', $quantity);
        }

        // Mark item as adjusted
        $item->markAsAdjusted($movement->id);
    }

    /**
     * Update stock count statistics
     */
    private function updateStockCountStats(StockCount $stockCount): void
    {
        $stats = $stockCount->items()->selectRaw('
            COUNT(*) as items_counted,
            SUM(CASE WHEN ABS(variance_quantity) > 0.0001 THEN 1 ELSE 0 END) as items_with_variance,
            SUM(variance_value) as total_variance_value
        ')->first();

        $stockCount->update([
            'items_counted' => $stats->items_counted ?? 0,
            'items_with_variance' => $stats->items_with_variance ?? 0,
            'total_variance_value' => $stats->total_variance_value ?? 0,
        ]);
    }

    /**
     * Generate reference number
     */
    private function generateReferenceNumber(int $warehouseId, string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $year = date('Y');
        $month = date('m');
        
        $lastCount = StockCount::where('warehouse_id', $warehouseId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastCount ? intval(substr($lastCount->reference_number, -4)) + 1 : 1;

        return sprintf('SC-%s-%d%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Get stock count summary
     */
    public function getStockCountSummary(StockCount $stockCount): array
    {
        $stats = $stockCount->items()->selectRaw('
            COUNT(*) as total_items,
            COUNT(counted_at) as counted_items,
            SUM(CASE WHEN counted_at IS NOT NULL AND ABS(variance_quantity) > 0.0001 THEN 1 ELSE 0 END) as items_with_variance,
            SUM(CASE WHEN counted_at IS NULL THEN 1 ELSE 0 END) as pending_items,
            SUM(variance_value) as total_variance_value,
            AVG(CASE WHEN counted_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, counted_at) END) as avg_count_time_minutes
        ')->first();

        return [
            'reference_number' => $stockCount->reference_number,
            'type' => $stockCount->type,
            'status' => $stockCount->status,
            'total_items' => $stats->total_items ?? 0,
            'counted_items' => $stats->counted_items ?? 0,
            'pending_items' => $stats->pending_items ?? 0,
            'items_with_variance' => $stats->items_with_variance ?? 0,
            'total_variance_value' => $stats->total_variance_value ?? 0,
            'average_count_time_minutes' => round($stats->avg_count_time_minutes ?? 0, 2),
            'progress_percentage' => $stats->total_items > 0 
                ? round(($stats->counted_items / $stats->total_items) * 100, 2) 
                : 0,
        ];
    }
}
