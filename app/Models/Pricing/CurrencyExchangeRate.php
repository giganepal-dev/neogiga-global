<?php

namespace App\Models\Pricing;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyExchangeRate extends BaseModel
{
    protected $table = 'currency_exchange_rates';

    protected $fillable = [
        'from_currency_code',
        'to_currency_code',
        'rate',
        'source',
        'valid_from',
        'valid_until',
        'is_active',
        'meta_data',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCurrencyPair($query, string $from, string $to)
    {
        return $query->where('from_currency_code', $from)
                     ->where('to_currency_code', $to);
    }
}
