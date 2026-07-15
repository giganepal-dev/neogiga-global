<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceBreak extends Model
{
    protected $fillable = [
        'product_id',
        'marketplace_id',
        'country_id',
        'min_quantity',
        'max_quantity',
        'unit_price',
        'currency_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'unit_price' => 'decimal:6',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('min_quantity');
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForCountry($query, $countryId)
    {
        return $query->where(function ($q) use ($countryId) {
            $q->where('country_id', $countryId)
              ->orWhereNull('country_id');
        });
    }

    public function getPriceForQuantity(int $quantity): ?float
    {
        if (! $this->is_active) {
            return null;
        }

        $min = $this->min_quantity;
        $max = $this->max_quantity;

        if ($quantity >= $min && ($max === null || $quantity <= $max)) {
            return (float) $this->unit_price;
        }

        return null;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->unit_price, 2);
    }

    public function getQuantityRangeAttribute(): string
    {
        $min = number_format($this->min_quantity);
        $max = $this->max_quantity !== null ? number_format($this->max_quantity) : '+';
        
        return "{$min}–{$max}";
    }
}
