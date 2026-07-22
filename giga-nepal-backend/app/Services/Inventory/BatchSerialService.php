<?php

namespace App\Services\Inventory;

use App\Models\Inventory\{InventoryBatch, SerialNumber, StockCount, StockCountItem};
use App\Models\Marketplace\{Product, ProductVariant, Warehouse, InventoryStock, InventoryMovement};
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Batch and Serial Tracking Service
 * 
 * Manages batch/lot tracking and serial number lifecycle
 */
class BatchSerialService
{
    /**
     * Create a new inventory batch
     */
    public function createBatch(array $data, int $userId): InventoryBatch
    {
        return DB::transaction(function () use ($data, $userId) {
            $batch = InventoryBatch::create([
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'batch_number' => $data['batch_number'] ?? $this->generateBatchNumber(),
                'lot_number' => $data['lot_number'] ?? null,
                'manufacturer_batch' => $data['manufacturer_batch'] ?? null,
                'manufacturing_date' => $data['manufacturing_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'best_before_date' => $data['best_before_date'] ?? null,
                'date_code' => $data['date_code'] ?? null,
                'country_of_origin' => $data['country_of_origin'] ?? null,
                'warranty_months' => $data['warranty_months'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? 0,
                'initial_quantity' => $data['quantity'] ?? 0,
                'current_quantity' => $data['quantity'] ?? 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'status' => InventoryBatch::STATUS_ACTIVE,
                'quality_status' => $data['require_inspection'] ? InventoryBatch::QUALITY_PENDING : InventoryBatch::QUALITY_PASSED,
                'quality_notes' => $data['quality_notes'] ?? null,
                'received_by' => $userId,
                'received_at' => now(),
                'certifications' => $data['certifications'] ?? null,
            ]);

            // Create or update inventory stock
            $this->updateOrCreateStock($batch, $data['quantity'] ?? 0);

            Log::info('Inventory batch created', [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'] ?? 0,
            ]);

            return $batch;
        });
    }

    /**
     * Add serial numbers to a batch
     */
    public function addSerialNumbers(int $batchId, array $serials, int $userId): int
    {
        return DB::transaction(function () use ($batchId, $serials, $userId) {
            $batch = InventoryBatch::findOrFail($batchId);
            $added = 0;

            foreach ($serials as $serialData) {
                $serial = SerialNumber::create([
                    'product_id' => $batch->product_id,
                    'product_variant_id' => $batch->product_variant_id,
                    'inventory_batch_id' => $batchId,
                    'warehouse_id' => $batch->warehouse_id,
                    'serial_number' => $serialData['serial_number'],
                    'manufacturer_serial' => $serialData['manufacturer_serial'] ?? null,
                    'status' => SerialNumber::STATUS_AVAILABLE,
                    'manufacturing_date' => $serialData['manufacturing_date'] ?? $batch->manufacturing_date,
                    'purchase_date' => $batch->received_at?->toDateString(),
                    'warranty_start_date' => $serialData['warranty_start_date'] ?? null,
                    'warranty_end_date' => $serialData['warranty_end_date'] ?? $this->calculateWarrantyEnd(
                        $serialData['warranty_start_date'] ?? $batch->received_at,
                        $serialData['warranty_months'] ?? $batch->warranty_months
                    ),
                    'purchase_cost' => $batch->unit_cost,
                ]);
                $added++;
            }

            Log::info('Serial numbers added to batch', [
                'batch_id' => $batchId,
                'count' => $added,
            ]);

            return $added;
        });
    }

    /**
     * Reserve a serial number for an order
     */
    public function reserveSerial(int $serialId, int $orderId): bool
    {
        $serial = SerialNumber::findOrFail($serialId);
        
        if ($serial->status !== SerialNumber::STATUS_AVAILABLE) {
            throw new \Exception("Serial number is not available (current status: {$serial->status})");
        }

        $serial->update([
            'status' => SerialNumber::STATUS_RESERVED,
            'current_order_id' => $orderId,
        ]);

        return true;
    }

    /**
     * Mark serial as sold
     */
    public function markSerialSold(int $serialId, int $orderId, int $customerId, float $salePrice): bool
    {
        return DB::transaction(function () use ($serialId, $orderId, $customerId, $salePrice) {
            $serial = SerialNumber::findOrFail($serialId);

            $serial->update([
                'status' => SerialNumber::STATUS_SOLD,
                'current_order_id' => null,
                'sold_to_customer_id' => $customerId,
                'sale_date' => now(),
                'sale_price' => $salePrice,
                'warranty_start_date' => $serial->warranty_start_date ?? now(),
            ]);

            // Update batch quantities
            $serial->batch->decrement('current_quantity', 1);

            return true;
        });
    }

    /**
     * Process batch quality inspection
     */
    public function inspectBatch(int $batchId, string $status, ?string $notes, int $inspectorId): InventoryBatch
    {
        $batch = InventoryBatch::findOrFail($batchId);

        $validStatuses = [
            InventoryBatch::QUALITY_PASSED,
            InventoryBatch::QUALITY_FAILED,
            InventoryBatch::QUALITY_QUARANTINED,
        ];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Invalid quality status');
        }

        $batch->update([
            'quality_status' => $status,
            'quality_notes' => $notes,
        ]);

        // If failed or quarantined, update batch status
        if ($status === InventoryBatch::QUALITY_FAILED) {
            $batch->update(['status' => InventoryBatch::STATUS_QUARANTINED]);
        }

        Log::info('Batch inspection completed', [
            'batch_id' => $batchId,
            'status' => $status,
            'inspector_id' => $inspectorId,
        ]);

        return $batch;
    }

    /**
     * Get batches expiring soon
     */
    public function getExpiringBatches(int $warehouseId, int $days = 30)
    {
        return InventoryBatch::where('warehouse_id', $warehouseId)
            ->active()
            ->expiringWithin($days)
            ->with(['product', 'warehouse'])
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get serial numbers under warranty expiring soon
     */
    public function getWarrantyExpiringSerials(int $warehouseId, int $days = 30)
    {
        return SerialNumber::where('warehouse_id', $warehouseId)
            ->underWarranty()
            ->warrantyExpiringWithin($days)
            ->with(['product', 'customer'])
            ->orderBy('warranty_end_date')
            ->get();
    }

    /**
     * Generate unique batch number
     */
    private function generateBatchNumber(): string
    {
        return 'BATCH-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Calculate warranty end date
     */
    private function calculateWarrantyEnd(?string $startDate, ?int $months): ?string
    {
        if (!$startDate || !$months) {
            return null;
        }
        return now()->parse($startDate)->addMonths($months)->toDateString();
    }

    /**
     * Update or create inventory stock for batch
     */
    private function updateOrCreateStock(InventoryBatch $batch, int $quantity): void
    {
        $stock = InventoryStock::firstOrCreate(
            [
                'product_id' => $batch->product_id,
                'product_variant_id' => $batch->product_variant_id,
                'warehouse_id' => $batch->warehouse_id,
                'inventory_batch_id' => $batch->id,
            ],
            [
                'quantity_on_hand' => $quantity,
                'unit_cost' => $batch->unit_cost,
                'last_movement_at' => now(),
            ]
        );

        if (!$stock->wasRecentlyCreated) {
            $stock->increment('quantity_on_hand', $quantity);
            $stock->update(['last_movement_at' => now()]);
        }
    }
}
