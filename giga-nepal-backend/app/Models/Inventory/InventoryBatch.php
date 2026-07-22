<?php

namespace App\Models\Inventory;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\Warehouse;
use App\Models\Vendor\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inventory Batch Model
 * 
 * Tracks batches/lots of inventory for expiry, warranty, and quality control
 */
class InventoryBatch extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_batches';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'warehouse_id',
        'supplier_id',
        'batch_number',
        'lot_number',
        'manufacturer_batch',
        'manufacturing_date',
        'expiry_date',
        'best_before_date',
        'date_code',
        'country_of_origin',
        'warranty_months',
        'unit_cost',
        'initial_quantity',
        'current_quantity',
        'reserved_quantity',
        'damaged_quantity',
        'status',
        'quality_status',
        'quality_notes',
        'received_by',
        'received_at',
        'certifications',
        'metadata',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'best_before_date' => 'date',
        'received_at' => 'datetime',
        'unit_cost' => 'decimal:4',
        'warranty_months' => 'integer',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'certifications' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_QUARANTINED = 'quarantined';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_RECALLED = 'recalled';
    public const STATUS_DEPLETED = 'depleted';

    /**
     * Quality status constants
     */
    public const QUALITY_PENDING = 'pending';
    public const QUALITY_PASSED = 'passed';
    public const QUALITY_FAILED = 'failed';
    public const QUALITY_QUARANTINED = 'quarantined';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_QUARANTINED => 'Quarantined',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_RECALLED => 'Recalled',
            self::STATUS_DEPLETED => 'Depleted',
        ];
    }

    public static function getQualityStatuses(): array
    {
        return [
            self::QUALITY_PENDING => 'Pending Inspection',
            self::QUALITY_PASSED => 'Passed',
            self::QUALITY_FAILED => 'Failed',
            self::QUALITY_QUARANTINED => 'Quarantined',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\SerialNumber::class, 'inventory_batch_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(\App\Models\Marketplace\InventoryMovement::class, 'inventory_batch_id');
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Check if batch is expiring soon
     * @param int $days Number of days to check
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->diffInDays(now(), false) <= $days && !$this->isExpired();
    }

    /**
     * Get available quantity (not reserved or damaged)
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->current_quantity - $this->reserved_quantity - $this->damaged_quantity);
    }

    /**
     * Check if batch has available stock
     */
    public function hasAvailableStock(): bool
    {
        return $this->available_quantity > 0;
    }

    /**
     * Scope for active batches only
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for batches expiring within given days
     */
    public function scopeExpiringWithin($query, int $days)
    {
        $date = now()->addDays($days);
        return $query->where('expiry_date', '<=', $date)
            ->where('expiry_date', '>=', now())
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for expired batches
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())
            ->where('status', '!=', self::STATUS_DEPLETED);
    }

    /**
     * Scope for batches requiring quality inspection
     */
    public function scopePendingInspection($query)
    {
        return $query->where('quality_status', self::QUALITY_PENDING);
    }

    /**
     * Scope for quarantined batches
     */
    public function scopeQuarantined($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_QUARANTINED)
                ->orWhere('quality_status', self::QUALITY_QUARANTINED);
        });
    }
}
