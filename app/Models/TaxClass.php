<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax Class Model
 * 
 * Represents a tax category/rate for a specific country.
 * Supports standard, reduced, zero, and exempt tax rates.
 */
class TaxClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_id',
        'name',
        'code',
        'rate',
        'description',
        'applicable_categories',
        'applicable_product_types',
        'is_compound',
        'is_shipping_taxable',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'applicable_categories' => 'array',
        'applicable_product_types' => 'array',
        'is_compound' => 'boolean',
        'is_shipping_taxable' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Get the country this tax class belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Scope to get only active tax classes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            });
    }

    /**
     * Check if tax class applies to a category.
     */
    public function appliesToCategory(int $categoryId): bool
    {
        if (empty($this->applicable_categories)) {
            return true; // Applies to all if not specified
        }
        
        return in_array($categoryId, $this->applicable_categories);
    }

    /**
     * Check if tax class applies to product type.
     */
    public function appliesToProductType(string $type): bool
    {
        if (empty($this->applicable_product_types)) {
            return true; // Applies to all if not specified
        }
        
        return in_array($type, $this->applicable_product_types);
    }

    /**
     * Calculate tax amount.
     */
    public function calculateTax(float $amount): float
    {
        return $amount * ($this->rate / 100);
    }

    /**
     * Get formatted rate as percentage.
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate, 2) . '%';
    }

    /**
     * Get standard tax class for a country.
     */
    public static function getStandardForCountry(int $countryId): ?self
    {
        return self::where('country_id', $countryId)
            ->where('code', 'STANDARD')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get reduced tax class for a country.
     */
    public static function getReducedForCountry(int $countryId): ?self
    {
        return self::where('country_id', $countryId)
            ->where('code', 'REDUCED')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get zero tax class for a country.
     */
    public static function getZeroForCountry(int $countryId): ?self
    {
        return self::where('country_id', $countryId)
            ->where('code', 'ZERO')
            ->where('is_active', true)
            ->first();
    }
}
