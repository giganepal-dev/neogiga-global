<?php

namespace App\Models\Inventory;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\Warehouse;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Serial Number Model
 * 
 * Tracks individual serialized items for warranty and service history
 */
class SerialNumber extends Model
{
    use SoftDeletes;

    protected $table = 'serial_numbers';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'inventory_batch_id',
        'warehouse_id',
        'serial_number',
        'manufacturer_serial',
        'status',
        'current_order_id',
        'sold_to_customer_id',
        'manufacturing_date',
        'purchase_date',
        'sale_date',
        'warranty_start_date',
        'warranty_end_date',
        'purchase_cost',
        'sale_price',
        'warranty_status',
        'warranty_notes',
        'service_history',
        'metadata',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'purchase_date' => 'date',
        'sale_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'purchase_cost' => 'decimal:4',
        'sale_price' => 'decimal:4',
        'service_history' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Status constants
     */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_SOLD = 'sold';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_LOST = 'lost';
    public const STATUS_IN_REPAIR = 'in_repair';

    /**
     * Warranty status constants
     */
    public const WARRANTY_ACTIVE = 'active';
    public const WARRANTY_EXPIRED = 'expired';
    public const WARRANTY_VOID = 'void';
    public const WARRANTY_CLAIMED = 'claimed';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_SOLD => 'Sold',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_DAMAGED => 'Damaged',
            self::STATUS_LOST => 'Lost',
            self::STATUS_IN_REPAIR => 'In Repair',
        ];
    }

    public static function getWarrantyStatuses(): array
    {
        return [
            self::WARRANTY_ACTIVE => 'Active',
            self::WARRANTY_EXPIRED => 'Expired',
            self::WARRANTY_VOID => 'Void',
            self::WARRANTY_CLAIMED => 'Claimed',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class, 'current_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'sold_to_customer_id');
    }

    /**
     * Check if serial is under warranty
     */
    public function isUnderWarranty(): bool
    {
        if (!$this->warranty_end_date) {
            return false;
        }
        
        return $this->warranty_end_date->isFuture() 
            && $this->warranty_status === self::WARRANTY_ACTIVE;
    }

    /**
     * Check if warranty is expiring soon
     * @param int $days Number of days to check
     */
    public function isWarrantyExpiringSoon(int $days = 30): bool
    {
        if (!$this->warranty_end_date || !$this->isUnderWarranty()) {
            return false;
        }
        
        return $this->warranty_end_date->diffInDays(now(), false) <= $days;
    }

    /**
     * Add service record to history
     */
    public function addServiceRecord(array $record): void
    {
        $history = $this->service_history ?? [];
        $record['date'] = now()->toIso8601String();
        $history[] = $record;
        $this->update(['service_history' => $history]);
    }

    /**
     * Get full service history
     */
    public function getServiceHistoryAttribute(): array
    {
        return $this->attributes['service_history'] ?? [];
    }

    /**
     * Scope for available serials only
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Scope for sold serials
     */
    public function scopeSold($query)
    {
        return $query->where('status', self::STATUS_SOLD);
    }

    /**
     * Scope for serials under warranty
     */
    public function scopeUnderWarranty($query)
    {
        return $query->where('warranty_status', self::WARRANTY_ACTIVE)
            ->where('warranty_end_date', '>=', now());
    }

    /**
     * Scope for serials with expiring warranty
     */
    public function scopeWarrantyExpiringWithin($query, int $days)
    {
        $date = now()->addDays($days);
        return $query->where('warranty_status', self::WARRANTY_ACTIVE)
            ->where('warranty_end_date', '<=', $date)
            ->where('warranty_end_date', '>=', now());
    }

    /**
     * Scope for serials in repair
     */
    public function scopeInRepair($query)
    {
        return $query->where('status', self::STATUS_IN_REPAIR);
    }
}
