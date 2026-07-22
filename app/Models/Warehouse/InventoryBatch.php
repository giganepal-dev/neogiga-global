<?php

namespace App\Models\Warehouse;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inventory Batch Model
 * 
 * Tracks batches/lots of products with expiry dates, quality status, and quantities.
 * Essential for electronics components with date codes, warranty tracking, and compliance.
 */
class InventoryBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_batches';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'supplier_id',
        'batch_number',
        'lot_number',
        'manufacturing_date',
        'expiry_date',
        'best_before_date',
        'date_code',
        'country_of_origin',
        'manufacturer_part_number',
        'quantity_received',
        'quantity_available',
        'quantity_reserved',
        'quantity_sold',
        'quantity_returned',
        'quantity_damaged',
        'unit_cost',
        'currency',
        'status',
        'quality_notes',
        'certifications',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'best_before_date' => 'date',
        'quantity_received' => 'decimal:4',
        'quantity_available' => 'decimal:4',
        'quantity_reserved' => 'decimal:4',
        'quantity_sold' => 'decimal:4',
        'quantity_returned' => 'decimal:4',
        'quantity_damaged' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'certifications' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_QUARANTINED = 'quarantined';
    const STATUS_EXPIRED = 'expired';
    const STATUS_RECALLED = 'recalled';
    const STATUS_CONSUMED = 'consumed';

    /**
     * Get the product this batch belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse where this batch is stored
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse\Warehouse::class);
    }

    /**
     * Get the supplier who provided this batch
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get serial numbers in this batch
     */
    public function serials(): HasMany
    {
        return $this->hasMany(InventorySerial::class, 'batch_id');
    }

    /**
     * Get stock count items for this batch
     */
    public function stockCountItems(): HasMany
    {
        return $this->hasMany(\App\Models\Warehouse\StockCountItem::class, 'batch_id');
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
     * 
     * @param int $days Number of days threshold
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return $this->expiry_date->diffInDays(now(), false) <= $days && !$this->isExpired();
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return $this->expiry_date->diffInDays(now(), false);
    }

    /**
     * Calculate total quantity in batch
     */
    public function getTotalQuantityAttribute(): float
    {
        return $this->quantity_available + 
               $this->quantity_reserved + 
               $this->quantity_sold + 
               $this->quantity_returned + 
               $this->quantity_damaged;
    }

    /**
     * Get batch value
     */
    public function getValueAttribute(): float
    {
        return $this->quantity_available * ($this->unit_cost ?? 0);
    }

    /**
     * Scope to get active batches
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get expired batches
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Scope to get batches expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>=', now());
    }

    /**
     * Scope to get batches by product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get batches by warehouse
     */
    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
