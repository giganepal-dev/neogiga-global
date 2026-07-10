<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Price List Model
 * 
 * Defines pricing tiers for specific country/currency combinations.
 * Supports retail, B2B, wholesale, contract, and promotional pricing.
 */
class PriceList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_id',
        'currency_id',
        'name',
        'code',
        'type',
        'is_default',
        'priority',
        'customer_groups',
        'seller_groups',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'priority' => 'integer',
        'customer_groups' => 'array',
        'seller_groups' => 'array',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the country this price list belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the currency this price list uses.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get price list items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    /**
     * Scope to get only active price lists.
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
     * Scope to get default price lists.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if price list applies to customer group.
     */
    public function appliesToCustomerGroup(string $group): bool
    {
        if (empty($this->customer_groups)) {
            return true; // Applies to all if not specified
        }

        return in_array($group, $this->customer_groups);
    }

    /**
     * Check if price list applies to seller group.
     */
    public function appliesToSellerGroup(string $group): bool
    {
        if (empty($this->seller_groups)) {
            return true; // Applies to all if not specified
        }

        return in_array($group, $this->seller_groups);
    }

    /**
     * Get price for a product from this price list.
     */
    public function getPriceForProduct(int $productId, int $quantity = 1): ?float
    {
        $item = $this->items()
            ->where('product_id', $productId)
            ->active()
            ->first();

        if (!$item) {
            return null;
        }

        return $item->getPriceForQuantity($quantity);
    }

    /**
     * Get or create default retail price list for country.
     */
    public static function getDefaultRetail(Country $country, Currency $currency): self
    {
        return self::firstOrCreate(
            [
                'country_id' => $country->id,
                'currency_id' => $currency->id,
                'type' => 'retail',
                'is_default' => true,
            ],
            [
                'name' => "{$country->name} Retail",
                'code' => strtoupper($country->iso_code_2) . '_RETAIL_' . $currency->code,
                'is_active' => true,
            ]
        );
    }

    /**
     * Get or create default B2B price list for country.
     */
    public static function getDefaultB2B(Country $country, Currency $currency): self
    {
        return self::firstOrCreate(
            [
                'country_id' => $country->id,
                'currency_id' => $currency->id,
                'type' => 'b2b',
                'is_default' => true,
            ],
            [
                'name' => "{$country->name} B2B",
                'code' => strtoupper($country->iso_code_2) . '_B2B_' . $currency->code,
                'is_active' => true,
            ]
        );
    }

    /**
     * Find best price list for customer type.
     */
    public static function findBestForCustomer(
        Country $country,
        Currency $currency,
        string $customerType = 'retail',
        array $customerGroups = []
    ): ?self {
        $query = self::where('country_id', $country->id)
            ->where('currency_id', $currency->id)
            ->where('type', $customerType)
            ->active()
            ->orderByDesc('priority');

        // If customer groups provided, try to find matching price list
        if (!empty($customerGroups)) {
            $priceLists = $query->get();
            
            foreach ($priceLists as $priceList) {
                foreach ($customerGroups as $group) {
                    if ($priceList->appliesToCustomerGroup($group)) {
                        return $priceList;
                    }
                }
            }
        }

        // Fall back to default
        return $query->default()->first() ?? $query->first();
    }
}
