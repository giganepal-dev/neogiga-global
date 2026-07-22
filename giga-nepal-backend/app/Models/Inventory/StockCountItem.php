<?php

namespace App\Models\Inventory;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\InventoryStock;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Count Item Model
 * 
 * Represents an individual item being counted in a stock count session
 */
class StockCountItem extends Model
{
    protected $table = 'stock_count_items';

    protected $fillable = [
        'stock_count_id',
        'product_id',
        'product_variant_id',
        'inventory_stock_id',
        'warehouse_bin_id',
        'inventory_batch_id',
        'counted_by',
        'reviewed_by',
        'expected_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'variance_value',
        'status',
        'variance_reason',
        'count_notes',
        'adjustment_notes',
        'requires_review',
        'counted_at',
        'reviewed_at',
        'metadata',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'variance_value' => 'decimal:4',
        'requires_review' => 'boolean',
        'counted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COUNTED = 'counted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_ADJUSTED = 'adjusted';

    /**
     * Variance reason constants
     */
    public const REASON_DAMAGE = 'damage';
    public const REASON_THEFT = 'theft';
    public const REASON_MISCOUNT = 'miscount';
    public const REASON_RECEIVING_ERROR = 'receiving_error';
    public const REASON_SHIPPING_ERROR = 'shipping_error';
    public const REASON_OTHER = 'other';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COUNTED => 'Counted',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_ADJUSTED => 'Adjusted',
        ];
    }

    public static function getVarianceReasons(): array
    {
        return [
            self::REASON_DAMAGE => 'Damage',
            self::REASON_THEFT => 'Theft/Loss',
            self::REASON_MISCOUNT => 'Miscount',
            self::REASON_RECEIVING_ERROR => 'Receiving Error',
            self::REASON_SHIPPING_ERROR => 'Shipping Error',
            self::REASON_OTHER => 'Other',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function inventoryStock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse\WarehouseBin::class, 'warehouse_bin_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Calculate variance automatically
     */
    public function calculateVariance(): void
    {
        $this->variance_quantity = $this->counted_quantity - $this->expected_quantity;
        $this->variance_value = $this->variance_quantity * ($this->unit_cost ?? 0);
        $this->requires_review = abs($this->variance_quantity) > 0;
    }

    /**
     * Record the count
     */
    public function recordCount(float $quantity, int $countedByUserId, ?string $notes = null): void
    {
        $this->update([
            'counted_quantity' => $quantity,
            'counted_by' => $countedByUserId,
            'counted_at' => now(),
            'status' => self::STATUS_COUNTED,
            'count_notes' => $notes,
        ]);
        
        $this->calculateVariance();
        $this->save();
    }

    /**
     * Review the count
     */
    public function review(int $reviewedByUserId, bool $approved, ?string $notes = null): void
    {
        $this->update([
            'reviewed_by' => $reviewedByUserId,
            'reviewed_at' => now(),
            'status' => $approved ? self::STATUS_REVIEWED : self::STATUS_COUNTED,
            'adjustment_notes' => $notes,
        ]);
    }

    /**
     * Check if item has positive variance (surplus)
     */
    public function hasSurplus(): bool
    {
        return $this->variance_quantity > 0;
    }

    /**
     * Check if item has negative variance (shortage)
     */
    public function hasShortage(): bool
    {
        return $this->variance_quantity < 0;
    }

    /**
     * Check if item requires review
     */
    public function needsReview(): bool
    {
        return $this->requires_review && $this->status === self::STATUS_COUNTED;
    }

    /**
     * Scope for items pending count
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for items requiring review
     */
    public function scopeRequiresReview($query)
    {
        return $query->where('requires_review', true)
            ->where('status', self::STATUS_COUNTED);
    }

    /**
     * Scope for items with positive variance
     */
    public function scopeWithSurplus($query)
    {
        return $query->where('variance_quantity', '>', 0);
    }

    /**
     * Scope for items with negative variance
     */
    public function scopeWithShortage($query)
    {
        return $query->where('variance_quantity', '<', 0);
    }
}
