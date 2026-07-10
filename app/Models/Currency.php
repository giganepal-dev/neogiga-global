<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Currency Model
 * 
 * Represents a currency with exchange rate tracking and formatting rules.
 * Supports multi-currency pricing and automatic conversion.
 */
class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'symbol_position',
        'decimal_places',
        'exchange_rate',
        'exchange_rate_date',
        'is_default',
        'is_active',
        'is_crypto',
        'central_bank_url',
    ];

    protected $casts = [
        'exchange_rate' => 'integer',
        'exchange_rate_date' => 'date',
        'decimal_places' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_crypto' => 'boolean',
    ];

    /**
     * The exchange rate multiplier for precision storage.
     */
    const RATE_MULTIPLIER = 100000;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($currency) {
            Cache::forget("currency:{$currency->code}");
            Cache::forget('currencies:active');
        });

        static::deleted(function ($currency) {
            Cache::forget("currency:{$currency->code}");
            Cache::forget('currencies:active');
        });
    }

    /**
     * Get countries using this currency.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_currency')
            ->withPivot(['is_primary', 'exchange_rate', 'exchange_rate_date', 'conversion_fee'])
            ->withTimestamps();
    }

    /**
     * Get price lists in this currency.
     */
    public function priceLists(): HasMany
    {
        return $this->hasMany(PriceList::class);
    }

    /**
     * Get exchange rate history.
     */
    public function exchangeRateHistoryFrom(): HasMany
    {
        return $this->hasMany(ExchangeRateHistory::class, 'from_currency', 'code');
    }

    public function exchangeRateHistoryTo(): HasMany
    {
        return $this->hasMany(ExchangeRateHistory::class, 'to_currency', 'code');
    }

    /**
     * Get actual exchange rate as float.
     */
    public function getExchangeRateFloatAttribute(): float
    {
        return $this->exchange_rate / self::RATE_MULTIPLIER;
    }

    /**
     * Set exchange rate from float (converts to integer for storage).
     */
    public function setExchangeRateFloatAttribute(float $rate): void
    {
        $this->attributes['exchange_rate'] = (int) round($rate * self::RATE_MULTIPLIER);
    }

    /**
     * Format amount with currency symbol.
     */
    public function format(float $amount, bool $showSymbol = true): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            '.',
            ','
        );

        if (!$showSymbol) {
            return $formatted;
        }

        return match ($this->symbol_position) {
            'before' => $this->symbol . $formatted,
            'after' => $formatted . $this->symbol,
            'space_before' => $this->symbol . ' ' . $formatted,
            'space_after' => $formatted . ' ' . $this->symbol,
            default => $this->symbol . $formatted,
        };
    }

    /**
     * Convert amount from base currency to this currency.
     */
    public function convertFromBase(float $baseAmount): float
    {
        return $baseAmount * $this->exchange_rate_float;
    }

    /**
     * Convert amount from this currency to base currency.
     */
    public function convertToBase(float $amount): float
    {
        return $amount / $this->exchange_rate_float;
    }

    /**
     * Convert amount between currencies.
     */
    public function convertTo(float $amount, self $targetCurrency): float
    {
        $baseAmount = $this->convertToBase($amount);
        return $targetCurrency->convertFromBase($baseAmount);
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default currency.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Find currency by code (cached).
     */
    public static function findByCode(string $code): ?self
    {
        $code = strtoupper($code);
        
        return Cache::remember(
            "currency:{$code}",
            now()->addHours(24),
            fn () => self::where('code', $code)->first()
        );
    }

    /**
     * Get all active currencies (cached).
     */
    public static function getActiveCurrencies(): array
    {
        return Cache::remember(
            'currencies:active',
            now()->addHours(24),
            fn () => self::active()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'symbol', 'symbol_position', 'decimal_places'])
                ->toArray()
        );
    }

    /**
     * Get default currency.
     */
    public static function getDefault(): ?self
    {
        return Cache::remember(
            'currency:default',
            now()->addHours(24),
            fn () => self::default()->first()
        );
    }
}
