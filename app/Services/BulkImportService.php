<?php

namespace App\Services;

use App\Models\SellerOffer;
use App\Models\Product;
use App\Models\Category;
use App\Models\VendorWarehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class BulkImportService
{
    protected $importStats = [
        'total_rows' => 0,
        'matched' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'duplicate' => 0,
        'invalid_mpn' => 0,
        'invalid_price' => 0,
        'invalid_currency' => 0,
        'invalid_category' => 0,
        'invalid_warehouse' => 0,
        'failed_rows' => 0,
    ];

    protected $errors = [];
    protected $matchedProducts = [];
    protected $unmatchedRecords = [];

    /**
     * Process product import CSV/XLSX
     */
    public function processProductImport(UploadedFile $file, int $vendorId, array $options): array
    {
        $this->resetStats();

        $marketplace = $options['marketplace'] ?? 'global';
        $warehouseId = $options['warehouse_id'] ?? null;

        // Validate warehouse if specified
        if ($warehouseId) {
            $warehouse = VendorWarehouse::where('id', $warehouseId)
                ->where('vendor_id', $vendorId)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->first();

            if (!$warehouse) {
                throw new \Exception('Invalid warehouse selected.');
            }
        }

        $path = $file->store('imports/products/' . $vendorId, 'private');
        $fullPath = Storage::disk('private')->path($path);

        try {
            Excel::import(new ProductImportHandler($vendorId, $marketplace, $warehouseId, $this), $fullPath);
        } catch (\Exception $e) {
            $this->importStats['failed_rows']++;
            $this->errors[] = 'Import failed: ' . $e->getMessage();
        }

        return [
            'stats' => $this->importStats,
            'errors' => $this->errors,
            'matched' => $this->matchedProducts,
            'unmatched' => $this->unmatchedRecords,
        ];
    }

    /**
     * Process offers/pricing import
     */
    public function processOffersImport(UploadedFile $file, int $vendorId, array $options): array
    {
        $this->resetStats();

        $marketplace = $options['marketplace'] ?? 'global';
        $warehouseId = $options['warehouse_id'] ?? null;

        if ($warehouseId) {
            $warehouse = VendorWarehouse::where('id', $warehouseId)
                ->where('vendor_id', $vendorId)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->firstOrFail();
        }

        $path = $file->store('imports/offers/' . $vendorId, 'private');
        $fullPath = Storage::disk('private')->path($path);

        try {
            Excel::import(new OfferImportHandler($vendorId, $marketplace, $warehouseId, $this), $fullPath);
        } catch (\Exception $e) {
            $this->importStats['failed_rows']++;
            $this->errors[] = 'Import failed: ' . $e->getMessage();
        }

        return [
            'stats' => $this->importStats,
            'errors' => $this->errors,
        ];
    }

    /**
     * Process stock import
     */
    public function processStockImport(UploadedFile $file, int $vendorId, array $options): array
    {
        $this->resetStats();

        $warehouseId = $options['warehouse_id'] ?? null;

        if (!$warehouseId) {
            throw new \Exception('Warehouse ID is required for stock import.');
        }

        $warehouse = VendorWarehouse::where('id', $warehouseId)
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->firstOrFail();

        $path = $file->store('imports/stock/' . $vendorId, 'private');
        $fullPath = Storage::disk('private')->path($path);

        try {
            Excel::import(new StockImportHandler($vendorId, $warehouseId, $this), $fullPath);
        } catch (\Exception $e) {
            $this->importStats['failed_rows']++;
            $this->errors[] = 'Import failed: ' . $e->getMessage();
        }

        return [
            'stats' => $this->importStats,
            'errors' => $this->errors,
        ];
    }

    /**
     * Preview import without committing
     */
    public function previewImport(UploadedFile $file, string $type, int $vendorId, array $options): array
    {
        $this->resetStats();

        // Read first 100 rows for preview
        $path = $file->store('imports/preview/' . $vendorId, 'private');
        $fullPath = Storage::disk('private')->path($path);

        $previewData = [];
        
        Excel::import(new class($vendorId, $options, $this, &$previewData) implements ToModel, WithHeadingRow {
            private $vendorId;
            private $options;
            private $service;
            private &$previewData;

            public function __construct($vendorId, $options, $service, &$previewData) {
                $this->vendorId = $vendorId;
                $this->options = $options;
                $this->service = $service;
                $this->previewData = &$previewData;
            }

            public function model(array $row) {
                if (count($this->previewData) < 100) {
                    $this->previewData[] = $row;
                }
                return null; // Don't actually import
            }
        }, $fullPath);

        return [
            'preview_rows' => $previewData,
            'total_estimated' => count($previewData),
            'columns' => $previewData ? array_keys($previewData[0]) : [],
        ];
    }

    /**
     * Generate import report
     */
    public function generateReport(array $results, string $format = 'csv'): string
    {
        $filename = 'import_report_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $path = 'imports/reports/' . $filename;

        if ($format === 'csv') {
            $content = "Row Type,MPN,Product Name,Status,Error\n";
            
            foreach ($results['matched'] ?? [] as $item) {
                $content .= "Matched,{$item['mpn']},{$item['product_name']},Success,\n";
            }

            foreach ($results['unmatched'] ?? [] as $item) {
                $content .= "Unmatched,{$item['mpn']},,Failed,Product not found in catalog\n";
            }

            foreach ($results['errors'] ?? [] as $error) {
                $content .= "Error,,,Failed,{$error}\n";
            }

            Storage::put($path, $content);
        }

        return $path;
    }

    /**
     * Match MPN against catalog
     */
    public function matchMpn(string $mpn): ?Product
    {
        $mpnService = app(MpnMatchingService::class);
        return $mpnService->searchByMpn($mpn);
    }

    /**
     * Validate price format
     */
    public function validatePrice($price): bool
    {
        return is_numeric($price) && $price >= 0;
    }

    /**
     * Validate currency
     */
    public function validateCurrency(string $currency): bool
    {
        $validCurrencies = ['USD', 'EUR', 'GBP', 'INR', 'NPR', 'AUD', 'BDT', 'LKR', 'BTN'];
        return in_array(strtoupper($currency), $validCurrencies);
    }

    /**
     * Validate category exists
     */
    public function validateCategory($categoryId): bool
    {
        if (!$categoryId) return false;
        return Category::find($categoryId) !== null;
    }

    /**
     * Reset stats for new import
     */
    protected function resetStats(): void
    {
        $this->importStats = [
            'total_rows' => 0,
            'matched' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'duplicate' => 0,
            'invalid_mpn' => 0,
            'invalid_price' => 0,
            'invalid_currency' => 0,
            'invalid_category' => 0,
            'invalid_warehouse' => 0,
            'failed_rows' => 0,
        ];
        $this->errors = [];
        $this->matchedProducts = [];
        $this->unmatchedRecords = [];
    }

    /**
     * Increment stat counter
     */
    public function incrementStat(string $key, int $amount = 1): void
    {
        if (isset($this->importStats[$key])) {
            $this->importStats[$key] += $amount;
        }
        $this->importStats['total_rows'] += $amount;
    }

    /**
     * Add error message
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Record matched product
     */
    public function recordMatched(array $data): void
    {
        $this->matchedProducts[] = $data;
        $this->incrementStat('matched');
    }

    /**
     * Record unmatched record
     */
    public function recordUnmatched(array $data): void
    {
        $this->unmatchedRecords[] = $data;
        $this->incrementStat('skipped');
    }
}

// Excel Import Handler for Products
class ProductImportHandler implements ToModel, WithHeadingRow, WithValidation
{
    private $vendorId;
    private $marketplace;
    private $warehouseId;
    private $service;

    public function __construct(int $vendorId, string $marketplace, ?int $warehouseId, BulkImportService $service)
    {
        $this->vendorId = $vendorId;
        $this->marketplace = $marketplace;
        $this->warehouseId = $warehouseId;
        $this->service = $service;
    }

    public function model(array $row)
    {
        DB::transaction(function () use ($row) {
            // Validate MPN
            if (empty($row['mpn'])) {
                $this->service->incrementStat('invalid_mpn');
                $this->service->addError("Row skipped: MPN is required");
                return null;
            }

            // Match product
            $product = $this->service->matchMpn($row['mpn']);

            if (!$product) {
                $this->service->recordUnmatched([
                    'mpn' => $row['mpn'],
                    'reason' => 'Product not found in catalog',
                ]);
                return null;
            }

            // Validate price
            if (!isset($row['price']) || !$this->service->validatePrice($row['price'])) {
                $this->service->incrementStat('invalid_price');
                $this->service->addError("Row skipped: Invalid price for MPN {$row['mpn']}");
                return null;
            }

            // Validate currency
            $currency = $row['currency'] ?? 'USD';
            if (!$this->service->validateCurrency($currency)) {
                $this->service->incrementStat('invalid_currency');
                $this->service->addError("Row skipped: Invalid currency {$currency} for MPN {$row['mpn']}");
                return null;
            }

            // Check for duplicate offer
            $existingOffer = SellerOffer::where('seller_id', $this->vendorId)
                ->where('product_id', $product->id)
                ->where('marketplace', $this->marketplace)
                ->where('warehouse_id', $this->warehouseId)
                ->where('status', 'active')
                ->first();

            if ($existingOffer) {
                $this->service->incrementStat('duplicate');
                $this->service->addError("Duplicate offer exists for MPN {$row['mpn']}");
                return null;
            }

            // Create offer
            SellerOffer::create([
                'seller_id' => $this->vendorId,
                'product_id' => $product->id,
                'marketplace' => $this->marketplace,
                'warehouse_id' => $this->warehouseId,
                'currency' => $currency,
                'base_price' => $row['price'],
                'available_quantity' => $row['quantity'] ?? 0,
                'moq' => $row['moq'] ?? 1,
                'lead_time_days' => $row['lead_time'] ?? null,
                'date_code' => $row['date_code'] ?? null,
                'condition' => $row['condition'] ?? 'new',
                'packaging' => $row['packaging'] ?? 'original',
                'status' => 'pending_approval',
            ]);

            $this->service->recordMatched([
                'mpn' => $row['mpn'],
                'product_name' => $product->name,
                'action' => 'created',
            ]);
            $this->service->incrementStat('created');
        });

        return null; // We handle creation manually
    }

    public function rules(): array
    {
        return [
            'mpn' => 'required|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|max:3',
        ];
    }
}

// Offer Import Handler
class OfferImportHandler implements ToModel, WithHeadingRow
{
    private $vendorId;
    private $marketplace;
    private $warehouseId;
    private $service;

    public function __construct(int $vendorId, string $marketplace, ?int $warehouseId, BulkImportService $service)
    {
        $this->vendorId = $vendorId;
        $this->marketplace = $marketplace;
        $this->warehouseId = $warehouseId;
        $this->service = $service;
    }

    public function model(array $row)
    {
        // Similar to ProductImportHandler but focused on updating existing offers
        // Implementation omitted for brevity - follows same pattern
        return null;
    }
}

// Stock Import Handler
class StockImportHandler implements ToModel, WithHeadingRow
{
    private $vendorId;
    private $warehouseId;
    private $service;

    public function __construct(int $vendorId, int $warehouseId, BulkImportService $service)
    {
        $this->vendorId = $vendorId;
        $this->warehouseId = $warehouseId;
        $this->service = $service;
    }

    public function model(array $row)
    {
        // Handle stock level updates
        // Implementation omitted for brevity
        return null;
    }
}
