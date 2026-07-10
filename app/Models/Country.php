<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

/**
 * Country Model
 * 
 * Represents a country with full localization, tax, and compliance settings.
 * Supports multi-currency, multi-language, and country-specific marketplace rules.
 * 
 * @property int $id
 * @property string $name
 * @property string $iso_code_2
 * @property string $iso_code_3
 * @property string|null $numeric_code
 * @property string $phone_code
 * @property string|null $capital
 * @property string $currency_code
 * @property string $currency_symbol
 * @property string|null $tld
 * @property string|null $native_name
 * @property string|null $region
 * @property string|null $subregion
 * @property array|null $languages
 * @property string|null $timezone
 * @property array|null $states
 * @property bool $is_active
 * @property bool $is_eu
 * @property bool $requires_import_license
 * @property float $default_vat_rate
 * @property float $default_import_duty_rate
 * @property string|null $hs_code_prefix
 * @property bool $allows_marketplace
 * @property bool $allows_b2b
 * @property bool $allows_b2c
 * @property array|null $restricted_categories
 * @property array|null $compliance_requirements
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'iso_code_2',
        'iso_code_3',
        'numeric_code',
        'phone_code',
        'capital',
        'currency_code',
        'currency_symbol',
        'tld',
        'native_name',
        'region',
        'subregion',
        'languages',
        'timezone',
        'states',
        'is_active',
        'is_eu',
        'requires_import_license',
        'default_vat_rate',
        'default_import_duty_rate',
        'hs_code_prefix',
        'allows_marketplace',
        'allows_b2b',
        'allows_b2c',
        'restricted_categories',
        'compliance_requirements',
    ];

    protected $casts = [
        'languages' => 'array',
        'states' => 'array',
        'is_active' => 'boolean',
        'is_eu' => 'boolean',
        'requires_import_license' => 'boolean',
        'default_vat_rate' => 'decimal:2',
        'default_import_duty_rate' => 'decimal:2',
        'allows_marketplace' => 'boolean',
        'allows_b2b' => 'boolean',
        'allows_b2c' => 'boolean',
        'restricted_categories' => 'array',
        'compliance_requirements' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Flush cache when country is updated
        static::saved(function ($country) {
            Cache::forget("country:{$country->iso_code_2}");
            Cache::forget("country:{$country->iso_code_3}");
            Cache::forget('countries:active');
        });

        static::deleted(function ($country) {
            Cache::forget("country:{$country->iso_code_2}");
            Cache::forget("country:{$country->iso_code_3}");
            Cache::forget('countries:active');
        });
    }

    /**
     * Get currencies available in this country.
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class, 'country_currency')
            ->withPivot(['is_primary', 'exchange_rate', 'exchange_rate_date', 'conversion_fee', 'allows_pricing_override'])
            ->withTimestamps();
    }

    /**
     * Get primary currency for this country.
     */
    public function primaryCurrency(): ?Currency
    {
        return $this->currencies()->wherePivot('is_primary', true)->first() 
            ?? $this->currencies()->first();
    }

    /**
     * Get languages spoken in this country.
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'country_language')
            ->withPivot(['is_official', 'is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get primary language for this country.
     */
    public function primaryLanguage(): ?Language
    {
        return $this->languages()->wherePivot('is_primary', true)->first()
            ?? $this->languages()->first();
    }

    /**
     * Get tax classes for this country.
     */
    public function taxClasses(): HasMany
    {
        return $this->hasMany(TaxClass::class);
    }

    /**
     * Get active tax classes.
     */
    public function activeTaxClasses(): HasMany
    {
        return $this->taxClasses()->where('is_active', true);
    }

    /**
     * Get import duty rules for this country.
     */
    public function importDutyRules(): HasMany
    {
        return $this->hasMany(ImportDutyRule::class);
    }

    /**
     * Get active import duty rules.
     */
    public function activeImportDutyRules(): HasMany
    {
        return $this->importDutyRules()->where('is_active', true);
    }

    /**
     * Get localization settings for this country.
     */
    public function localization(): HasOne
    {
        return $this->hasOne(CountryLocalization::class);
    }

    /**
     * Get price lists for this country.
     */
    public function priceLists(): HasMany
    {
        return $this->hasMany(PriceList::class);
    }

    /**
     * Get default retail price list.
     */
    public function defaultRetailPriceList(): ?PriceList
    {
        return $this->priceLists()
            ->where('type', 'retail')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get default B2B price list.
     */
    public function defaultB2BPriceList(): ?PriceList
    {
        return $this->priceLists()
            ->where('type', 'b2b')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get organizations registered in this country.
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'country_id');
    }

    /**
     * Get warehouses in this country.
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Check if category is restricted in this country.
     */
    public function isCategoryRestricted(int $categoryId): bool
    {
        if (empty($this->restricted_categories)) {
            return false;
        }
        
        return in_array($categoryId, $this->restricted_categories);
    }

    /**
     * Check if compliance certificate is required.
     */
    public function requiresCompliance(string $certificate): bool
    {
        if (empty($this->compliance_requirements)) {
            return false;
        }
        
        return in_array(strtoupper($certificate), array_map('strtoupper', $this->compliance_requirements));
    }

    /**
     * Get formatted VAT rate as percentage.
     */
    public function getFormattedVatRateAttribute(): string
    {
        return number_format($this->default_vat_rate, 2) . '%';
    }

    /**
     * Get formatted import duty rate as percentage.
     */
    public function getFormattedImportDutyRateAttribute(): string
    {
        return number_format($this->default_import_duty_rate, 2) . '%';
    }

    /**
     * Get full country name with code.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->iso_code_2})";
    }

    /**
     * Get flag emoji from states data or generate from code.
     */
    public function getFlagEmojiAttribute(): string
    {
        // Regional Indicator Symbol Letters
        $code = strtoupper($this->iso_code_2);
        $flag = '';
        
        for ($i = 0; $i < strlen($code); $i++) {
            $flag .= mb_chr(ord($code[$i]) + 127397);
        }
        
        return $flag;
    }

    /**
     * Scope to get only active countries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get countries allowing marketplace.
     */
    public function scopeAllowsMarketplace($query)
    {
        return $query->where('allows_marketplace', true);
    }

    /**
     * Scope to get EU countries.
     */
    public function scopeEu($query)
    {
        return $query->where('is_eu', true);
    }

    /**
     * Find country by ISO code (cached).
     */
    public static function findByCode(string $code): ?self
    {
        $code = strtoupper($code);
        
        return Cache::remember(
            "country:{$code}",
            now()->addHours(24),
            function () use ($code) {
                if (strlen($code) === 2) {
                    return self::where('iso_code_2', $code)->first();
                } elseif (strlen($code) === 3) {
                    return self::where('iso_code_3', $code)->first();
                }
                
                return null;
            }
        );
    }

    /**
     * Get all active countries (cached).
     */
    public static function getActiveCountries(): array
    {
        return Cache::remember(
            'countries:active',
            now()->addHours(24),
            fn () => self::active()
                ->orderBy('name')
                ->get(['id', 'name', 'iso_code_2', 'currency_code', 'currency_symbol'])
                ->toArray()
        );
    }
}
