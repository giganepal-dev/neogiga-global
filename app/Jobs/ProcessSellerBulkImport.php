<?php

namespace App\Jobs;

use App\Models\SellerImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ProcessSellerBulkImport extends ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $import;
    public $filePath;

    public function __construct(SellerImport $import)
    {
        $this->import = $import;
        $this->filePath = $import->file_path;
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $this->processFile();
            });

            $this->import->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            // Send completion notification
            \App\Models\SellerNotification::create([
                'user_id' => $this->import->vendor->user_id,
                'type' => 'import_completed',
                'title' => 'Import Completed',
                'message' => "Your {$this->import->import_type} import has been processed successfully.",
                'data' => ['import_id' => $this->import->id],
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk import failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            \App\Models\SellerNotification::create([
                'user_id' => $this->import->vendor->user_id,
                'type' => 'import_failed',
                'title' => 'Import Failed',
                'message' => "Your {$this->import->import_type} import failed: {$e->getMessage()}",
                'data' => ['import_id' => $this->import->id],
            ]);

            throw $e;
        }
    }

    protected function processFile(): void
    {
        $disk = config('filesystems.default');
        $fullPath = Storage::disk($disk)->path($this->filePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("Import file not found: {$this->filePath}");
        }

        $csv = Reader::createFromPath($fullPath, 'r');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv);
        $totalRows = count($records);
        $processed = 0;
        $success = 0;
        $failed = 0;
        $duplicates = 0;
        $errors = [];

        foreach ($records as $index => $row) {
            try {
                $this->processRow($row, $index + 2); // +2 for header and 1-based index
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ];
            }

            $processed++;

            // Update progress every 100 rows
            if ($processed % 100 === 0) {
                $this->import->update([
                    'processed_rows' => $processed,
                    'successful_rows' => $success,
                    'failed_rows' => $failed,
                ]);
            }
        }

        $this->import->update([
            'total_rows' => $totalRows,
            'processed_rows' => $processed,
            'successful_rows' => $success,
            'failed_rows' => $failed,
            'duplicate_rows' => $duplicates,
            'errors' => $errors,
        ]);
    }

    protected function processRow(array $row, int $rowNumber): void
    {
        switch ($this->import->import_type) {
            case 'products':
                $this->processProductRow($row, $rowNumber);
                break;
            case 'stock':
                $this->processStockRow($row, $rowNumber);
                break;
            case 'pricing':
                $this->processPricingRow($row, $rowNumber);
                break;
            default:
                throw new \Exception("Unknown import type: {$this->import->import_type}");
        }
    }

    protected function processProductRow(array $row, int $rowNumber): void
    {
        // Validate required fields
        if (empty($row['mpn']) && empty($row['manufacturer']) && empty($row['product_name'])) {
            throw new \Exception("Row {$rowNumber}: Missing required fields (MPN or Manufacturer + Product Name)");
        }

        // Try to match existing product
        $mpnMatchingService = app(\App\Services\MpnMatchingService::class);
        $match = $mpnMatchingService->searchByMpn($row['mpn'] ?? null);

        if (!$match && !empty($row['manufacturer']) && !empty($row['mpn'])) {
            $match = $mpnMatchingService->searchByManufacturerAndMpn($row['manufacturer'], $row['mpn']);
        }

        if ($match) {
            // Product exists - create offer if pricing provided
            if (!empty($row['price']) && !empty($row['currency'])) {
                $warehouseId = $this->findWarehouseId($row['warehouse_code'] ?? $row['warehouse_name']);
                if (!$warehouseId) {
                    throw new \Exception("Row {$rowNumber}: Warehouse not found");
                }

                $mpnMatchingService->createOffer(
                    $match['product'],
                    $this->import->vendor,
                    $warehouseId,
                    $this->import->marketplace_id,
                    (float) $row['price'],
                    $row['currency'],
                    (int) ($row['quantity'] ?? 0),
                    $row
                );
            }
        } else {
            // No match - create product request
            if (empty($row['category_id'])) {
                throw new \Exception("Row {$rowNumber}: Category required for new product");
            }

            // Create product request (would need a service method for this)
            // For now, skip with error
            throw new \Exception("Row {$rowNumber}: No matching product found. New product requests must be created manually.");
        }
    }

    protected function processStockRow(array $row, int $rowNumber): void
    {
        if (empty($row['mpn']) || empty($row['warehouse_code'])) {
            throw new \Exception("Row {$rowNumber}: MPN and warehouse code are required");
        }

        $mpnMatchingService = app(\App\Services\MpnMatchingService::class);
        $match = $mpnMatchingService->searchByMpn($row['mpn']);

        if (!$match) {
            throw new \Exception("Row {$rowNumber}: Product not found for MPN: {$row['mpn']}");
        }

        $warehouseId = $this->findWarehouseId($row['warehouse_code']);
        if (!$warehouseId) {
            throw new \Exception("Row {$rowNumber}: Warehouse not found");
        }

        $quantity = (int) ($row['quantity'] ?? 0);
        if ($quantity <= 0) {
            throw new \Exception("Row {$rowNumber}: Quantity must be positive");
        }

        // Find or create offer
        $offer = \App\Models\SellerOffer::firstOrCreate(
            [
                'vendor_id' => $this->import->vendor->id,
                'product_id' => $match['product']->id,
                'warehouse_id' => $warehouseId,
                'marketplace_id' => $this->import->marketplace_id ?? null,
            ],
            [
                'currency' => $row['currency'] ?? 'USD',
                'base_price' => (float) ($row['price'] ?? 0),
                'status' => 'pending_approval',
            ]
        );

        // Add stock movement
        \App\Models\SellerInventoryMovement::create([
            'seller_offer_id' => $offer->id,
            'movement_type' => 'manual_increase',
            'quantity_change' => $quantity,
            'quantity_before' => $offer->available_quantity ?? 0,
            'quantity_after' => ($offer->available_quantity ?? 0) + $quantity,
            'reference_type' => 'bulk_import',
            'reference_id' => $this->import->id,
            'notes' => "Bulk import row {$rowNumber}",
        ]);

        $offer->increment('available_quantity', $quantity);
    }

    protected function processPricingRow(array $row, int $rowNumber): void
    {
        if (empty($row['mpn'])) {
            throw new \Exception("Row {$rowNumber}: MPN is required");
        }

        if (empty($row['price']) || empty($row['currency'])) {
            throw new \Exception("Row {$rowNumber}: Price and currency are required");
        }

        $mpnMatchingService = app(\App\Services\MpnMatchingService::class);
        $match = $mpnMatchingService->searchByMpn($row['mpn']);

        if (!$match) {
            throw new \Exception("Row {$rowNumber}: Product not found");
        }

        $warehouseId = $this->findWarehouseId($row['warehouse_code'] ?? null);
        
        $offer = \App\Models\SellerOffer::where('vendor_id', $this->import->vendor->id)
            ->where('product_id', $match['product']->id)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('marketplace_id', $this->import->marketplace_id ?? null)
            ->first();

        if (!$offer) {
            throw new \Exception("Row {$rowNumber}: No existing offer found for this product");
        }

        $offer->update([
            'base_price' => (float) $row['price'],
            'currency' => $row['currency'],
            'discount_percentage' => (float) ($row['discount'] ?? 0),
            'moq' => (int) ($row['moq'] ?? 1),
            'updated_at' => now(),
        ]);
    }

    protected function findWarehouseId(?string $code): ?int
    {
        if (!$code) {
            return $this->import->vendor->warehouses()->where('is_verified', true)->first()?->id;
        }

        $warehouse = $this->import->vendor->warehouses()
            ->whereIn('warehouse_code', [$code, strtoupper($code)])
            ->orWhere('warehouse_name', 'like', "%{$code}%")
            ->first();

        return $warehouse?->id;
    }
}
