<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Import Duty Rule Model
 * 
 * Defines import duty rates based on HS Code patterns for specific countries.
 * Supports preferential rates for trade agreements.
 */
class ImportDutyRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_id',
        'hs_code_pattern',
        'duty_rate',
        'vat_rate',
        'excise_rate',
        'origin_country_preference',
        'preferential_rate',
        'notes',
        'requires_certificate',
        'required_certificates',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'duty_rate' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'excise_rate' => 'decimal:2',
        'preferential_rate' => 'decimal:2',
        'requires_certificate' => 'boolean',
        'required_certificates' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Get the country this rule applies to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Scope to get only active rules.
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
     * Check if HS code matches this rule's pattern.
     */
    public function matchesHsCode(string $hsCode): bool
    {
        $pattern = $this->hs_code_pattern;
        
        // Convert wildcard pattern to regex
        $regex = str_replace(['*', '.'], ['.*', '\.'], $pattern);
        $regex = '/^' . $regex . '/i';
        
        return (bool) preg_match($regex, $hsCode);
    }

    /**
     * Get applicable duty rate for a product.
     */
    public function getDutyRateForProduct(string $originCountry, string $hsCode): float
    {
        if (!$this->matchesHsCode($hsCode)) {
            return 0.0;
        }

        // Check for preferential rate based on origin
        if ($this->origin_country_preference && 
            strtoupper($this->origin_country_preference) === strtoupper($originCountry)) {
            return $this->preferential_rate ?? $this->duty_rate;
        }

        return $this->duty_rate;
    }

    /**
     * Calculate import duty amount.
     */
    public function calculateDuty(float $customsValue, string $originCountry, string $hsCode): float
    {
        $rate = $this->getDutyRateForProduct($originCountry, $hsCode);
        return $customsValue * ($rate / 100);
    }

    /**
     * Calculate VAT on imported goods (includes duty in base).
     */
    public function calculateImportVat(float $customsValue, float $dutyAmount): float
    {
        $vatRate = $this->vat_rate ?? $this->country->default_vat_rate;
        $vatBase = $customsValue + $dutyAmount;
        
        return $vatBase * ($vatRate / 100);
    }

    /**
     * Calculate total import costs.
     */
    public function calculateTotalImportCost(
        float $customsValue,
        string $originCountry,
        string $hsCode
    ): array {
        $duty = $this->calculateDuty($customsValue, $originCountry, $hsCode);
        $vat = $this->calculateImportVat($customsValue, $duty);
        $excise = $customsValue * ($this->excise_rate / 100);
        
        return [
            'customs_value' => $customsValue,
            'duty' => $duty,
            'vat' => $vat,
            'excise' => $excise,
            'total' => $customsValue + $duty + $vat + $excise,
        ];
    }

    /**
     * Find matching rule for HS code and country.
     */
    public static function findForHsCode(int $countryId, string $hsCode): ?self
    {
        $rules = self::where('country_id', $countryId)
            ->active()
            ->orderByRaw("LENGTH(hs_code_pattern) DESC") // Most specific first
            ->get();

        foreach ($rules as $rule) {
            if ($rule->matchesHsCode($hsCode)) {
                return $rule;
            }
        }

        return null;
    }
}
