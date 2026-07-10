<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseShipmentItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'shipment_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'batch_number',
        'expiry_date',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the shipment that owns this item
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(WarehouseShipment::class);
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
     * Calculate total cost for this item
     */
    public function getTotalCostAttribute(): float
    {
        return $this->quantity * ($this->unit_cost ?? 0);
    }

    /**
     * Calculate total price for this item
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->quantity * ($this->unit_price ?? 0);
    }
}
