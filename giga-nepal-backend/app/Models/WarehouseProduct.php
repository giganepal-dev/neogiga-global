<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_incoming',
        'reorder_level',
        'reorder_quantity',
        'cost_price',
        'selling_price',
        'bin_location',
        'zone',
        'last_counted_at',
        'last_restocked_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_incoming' => 'integer',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'last_counted_at' => 'date',
        'last_restocked_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the warehouse that owns this product stock
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_available', '<=', 'reorder_level');
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_available', 0);
    }

    /**
     * Scope for products with incoming stock
     */
    public function scopeWithIncomingStock($query)
    {
        return $query->where('quantity_incoming', '>', 0);
    }

    /**
     * Check if product needs reordering
     */
    public function needsReordering(): bool
    {
        return $this->quantity_available <= $this->reorder_level;
    }

    /**
     * Reserve quantity for an order
     */
    public function reserveQuantity(int $quantity): bool
    {
        if ($this->quantity_available >= $quantity) {
            $this->decrement('quantity_available', $quantity);
            $this->increment('quantity_reserved', $quantity);
            return true;
        }
        return false;
    }

    /**
     * Release reserved quantity
     */
    public function releaseQuantity(int $quantity): void
    {
        $releaseQty = min($quantity, $this->quantity_reserved);
        $this->increment('quantity_available', $releaseQty);
        $this->decrement('quantity_reserved', $releaseQty);
    }

    /**
     * Convert reserved to sold (on order completion)
     */
    public function completeSale(int $quantity): void
    {
        $this->decrement('quantity_reserved', min($quantity, $this->quantity_reserved));
    }

    /**
     * Add incoming stock
     */
    public function addStock(int $quantity, ?float $costPrice = null): void
    {
        $this->increment('quantity_available', $quantity);
        
        if ($costPrice !== null) {
            $this->cost_price = $costPrice;
        }
        
        $this->last_restocked_at = now();
        $this->save();
    }
}
