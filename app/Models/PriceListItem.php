<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Price List Item Model
 * 
 * Individual product pricing within a price list.
 * Supports tiered/volume pricing.
 */
class PriceListItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'price_list_id',
        'product_id',
        'base_price',
        'min_quantity',
        'max_quantity',
        'tier_prices',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'base_price' => 'decimal:4',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'tier_prices' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get the price list this item belongs to.
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * Scope to get only active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            });
    }

    /**
     * Get price for a specific quantity (with tier pricing).
     */
    public function getPriceForQuantity(int $quantity): float
    {
        // Check tier prices first
        if (!empty($this->tier_prices)) {
            foreach ($this->tier_prices as $tier) {
                $minQty = $tier['min_quantity'] ?? 0;
                $maxQty = $tier['max_quantity'] ?? null;
                $price = $tier['price'] ?? $this->base_price;

                if ($quantity >= $minQty && ($maxQty === null || $quantity <= $maxQty)) {
                    return $price;
                }
            }
        }

        // Fall back to base price
        return $this->base_price;
    }

    /**
     * Calculate total price for quantity.
     */
    public function calculateTotal(int $quantity): float
    {
        return $this->getPriceForQuantity($quantity) * $quantity;
    }
}
