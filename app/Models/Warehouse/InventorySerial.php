<?php

namespace App\Models\Warehouse;

use App\Models\Marketplace\Customer;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inventory Serial Model
 * 
 * Tracks individual serial numbers for products requiring unique identification.
 * Essential for high-value electronics, warranty tracking, and compliance.
 */
class InventorySerial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_serials';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'batch_id',
        'serial_number',
        'manufacturer_serial',
        'manufacturing_date',
        'warranty_start_date',
        'warranty_end_date',
        'warranty_months',
        'warranty_provider',
        'status',
        'assigned_customer_id',
        'sale_id',
        'notes',
        'test_results',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'test_results' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_IN_STOCK = 'in_stock';
    const STATUS_RESERVED = 'reserved';
    const STATUS_SOLD = 'sold';
    const STATUS_RETURNED = 'returned';
    const STATUS_DAMAGED = 'damaged';
    const STATUS_LOST = 'lost';
    const STATUS_IN_REPAIR = 'in_repair';
    const STATUS_QUARANTINED = 'quarantined';

    /**
     * Get the product this serial belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse where this serial is stored
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the batch this serial belongs to
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    /**
     * Get the customer this serial is assigned to
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'assigned_customer_id');
    }

    /**
     * Get the sale/order this serial was sold in
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sale_id');
    }

    /**
     * Check if serial is in stock
     */
    public function isInStock(): bool
    {
        return $this->status === self::STATUS_IN_STOCK;
    }

    /**
     * Check if serial is sold
     */
    public function isSold(): bool
    {
        return $this->status === self::STATUS_SOLD;
    }

    /**
     * Check if warranty is valid
     */
    public function isUnderWarranty(): bool
    {
        if (!$this->warranty_end_date) {
            return false;
        }
        
        return $this->warranty_end_date->isFuture();
    }

    /**
     * Get days remaining on warranty
     */
    public function warrantyDaysRemaining(): ?int
    {
        if (!$this->warranty_end_date) {
            return null;
        }
        
        return $this->warranty_end_date->diffInDays(now(), false);
    }

    /**
     * Check if warranty is expired
     */
    public function isWarrantyExpired(): bool
    {
        if (!$this->warranty_end_date) {
            return false;
        }
        
        return $this->warranty_end_date->isPast();
    }

    /**
     * Scope to get in-stock serials
     */
    public function scopeInStock($query)
    {
        return $query->where('status', self::STATUS_IN_STOCK);
    }

    /**
     * Scope to get sold serials
     */
    public function scopeSold($query)
    {
        return $query->where('status', self::STATUS_SOLD);
    }

    /**
     * Scope to get serials by product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get serials by warehouse
     */
    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope to get serials under warranty
     */
    public function scopeUnderWarranty($query)
    {
        return $query->where('warranty_end_date', '>=', now());
    }

    /**
     * Scope to get serials with expiring warranty
     */
    public function scopeWarrantyExpiringSoon($query, int $days = 30)
    {
        return $query->where('warranty_end_date', '<=', now()->addDays($days))
                    ->where('warranty_end_date', '>=', now());
    }
}
