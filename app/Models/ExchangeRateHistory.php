<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exchange Rate History Model
 * 
 * Tracks historical exchange rates for currency conversion auditing.
 */
class ExchangeRateHistory extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'source',
    ];

    protected $casts = [
        'rate' => 'integer',
        'effective_date' => 'date',
    ];

    const RATE_MULTIPLIER = 100000;

    /**
     * Get actual exchange rate as float.
     */
    public function getRateFloatAttribute(): float
    {
        return $this->rate / self::RATE_MULTIPLIER;
    }

    /**
     * Record a new exchange rate.
     */
    public static function recordRate(
        string $fromCurrency,
        string $toCurrency,
        float $rate,
        ?string $source = null,
        ?\DateTime $effectiveDate = null
    ): self {
        return self::create([
            'from_currency' => strtoupper($fromCurrency),
            'to_currency' => strtoupper($toCurrency),
            'rate' => (int) round($rate * self::RATE_MULTIPLIER),
            'effective_date' => $effectiveDate ?? now(),
            'source' => $source,
        ]);
    }

    /**
     * Get rate for a specific date.
     */
    public static function getRateForDate(
        string $fromCurrency,
        string $toCurrency,
        \DateTime $date
    ): ?float {
        $record = self::where('from_currency', strtoupper($fromCurrency))
            ->where('to_currency', strtoupper($toCurrency))
            ->where('effective_date', '<=', $date->format('Y-m-d'))
            ->orderByDesc('effective_date')
            ->first();

        return $record?->rate_float;
    }

    /**
     * Get latest rate.
     */
    public static function getLatestRate(string $fromCurrency, string $toCurrency): ?float
    {
        return self::getRateForDate($fromCurrency, $toCurrency, now());
    }
}
