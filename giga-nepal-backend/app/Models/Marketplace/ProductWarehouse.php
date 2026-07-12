<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWarehouse extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'incoming_quantity',
        'last_restocked_at',
        'bin_location',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'incoming_quantity' => 'integer',
        'last_restocked_at' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getAvailableQuantityAttribute()
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
