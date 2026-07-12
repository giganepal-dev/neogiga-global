<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCountryPrice extends Model
{
    protected $fillable = [
        'product_id',
        'country_id',
        'base_price',
        'sale_price',
        'bulk_price',
        'bulk_min_quantity',
        'currency',
        'is_available',
        'price_valid_from',
        'price_valid_until',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'bulk_price' => 'decimal:2',
        'bulk_min_quantity' => 'integer',
        'is_available' => 'boolean',
        'price_valid_from' => 'date',
        'price_valid_until' => 'date',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function getPriceAttribute()
    {
        return $this->sale_price ?? $this->base_price;
    }
}
