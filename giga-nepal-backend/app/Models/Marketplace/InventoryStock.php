<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'warehouse_id',
        'marketplace_id',
        'vendor_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_damaged',
        'quantity_incoming',
        'reorder_level',
        'reorder_quantity',
        'batch_number',
        'serial_number',
        'expiry_date',
        'last_counted_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'last_counted_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'stock_id');
    }

    public function getTotalQuantityAttribute()
    {
        return $this->quantity_available + 
               $this->quantity_reserved + 
               $this->quantity_damaged + 
               $this->quantity_incoming;
    }

    public function isLowStock()
    {
        return $this->quantity_available <= $this->reorder_level;
    }
}
