<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Barcode Model
 * 
 * Represents a barcode associated with a product, variant, or warehouse location.
 * Supports multiple barcode types and prevents duplicates.
 */
class ProductBarcode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'product_warehouse_id',
        'barcode_value',
        'barcode_type',
        'barcode_format',
        'source',
        'is_primary',
        'is_active',
        'gs1_company_prefix',
        'check_digit',
        'metadata',
        'verified_at',
        'verified_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Barcode type constants
     */
    public const TYPE_CODE128 = 'code128';
    public const TYPE_CODE39 = 'code39';
    public const TYPE_EAN13 = 'ean13';
    public const TYPE_EAN8 = 'ean8';
    public const TYPE_UPCA = 'upca';
    public const TYPE_UPCE = 'upce';
    public const TYPE_QR = 'qr';
    public const TYPE_DATAMATRIX = 'datamatrix';

    /**
     * Barcode source constants
     */
    public const SOURCE_MANUFACTURER = 'manufacturer';
    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_SUPPLIER = 'supplier';
    public const SOURCE_CUSTOM = 'custom';

    /**
     * Get the product this barcode belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant if applicable
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the warehouse-specific product record if applicable
     */
    public function productWarehouse(): BelongsTo
    {
        return $this->belongsTo(ProductWarehouse::class, 'product_warehouse_id');
    }

    /**
     * Get the user who verified this barcode
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user who created this barcode
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get scan logs for this barcode
     */
    public function scanLogs()
    {
        return $this->hasMany(ProductBarcodeScanLog::class, 'product_barcode_id');
    }

    /**
     * Scope to get only active barcodes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only primary barcodes
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to filter by barcode type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('barcode_type', $type);
    }

    /**
     * Scope to filter by source
     */
    public function scopeOfSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Check if this is a manufacturer-provided barcode
     */
    public function isManufacturerBarcode(): bool
    {
        return $this->source === self::SOURCE_MANUFACTURER;
    }

    /**
     * Generate SVG representation of this barcode
     */
    public function generateSvg(int $height = 60, float $barWidth = 1.5): string
    {
        $barcodeService = app(\App\Services\Labels\BarcodeService::class);
        
        switch ($this->barcode_type) {
            case self::TYPE_CODE128:
                return $barcodeService->code128($this->barcode_value, $height, $barWidth);
            case self::TYPE_CODE39:
                // Code39 generation (simplified - would need full implementation)
                return $barcodeService->code128($this->barcode_value, $height, $barWidth);
            case self::TYPE_QR:
                return $barcodeService->qrCode($this->barcode_value, $height);
            default:
                // Default to Code128 for unknown types
                return $barcodeService->code128($this->barcode_value, $height, $barWidth);
        }
    }

    /**
     * Validate the barcode check digit for EAN/UPC codes
     */
    public function validateCheckDigit(): bool
    {
        if (!in_array($this->barcode_type, [self::TYPE_EAN13, self::TYPE_EAN8, self::TYPE_UPCA, self::TYPE_UPCE])) {
            return true; // Not applicable for non-EAN/UPC codes
        }

        return $this->calculateCheckDigit() === $this->check_digit;
    }

    /**
     * Calculate check digit for EAN/UPC codes
     */
    private function calculateCheckDigit(): ?string
    {
        if (!in_array($this->barcode_type, [self::TYPE_EAN13, self::TYPE_EAN8, self::TYPE_UPCA, self::TYPE_UPCE])) {
            return null;
        }

        $digits = preg_split('//', $this->barcode_value, -1, PREG_SPLIT_NO_EMPTY);
        // Remove check digit from calculation
        array_pop($digits);
        
        $sum = 0;
        foreach ($digits as $i => $digit) {
            $weight = ($this->barcode_type === self::TYPE_EAN13 || $this->barcode_type === self::TYPE_EAN8)
                ? (($i % 2 === 0) ? 1 : 3)
                : (($i % 2 === 0) ? 3 : 1);
            $sum += (int) $digit * $weight;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return (string) $checkDigit;
    }
}
