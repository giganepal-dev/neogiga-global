<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only exchange-rate history (see EXCHANGE_RATE_GUIDE.md): rows are
 * only ever inserted, never mutated — the latest fetched_at per pair wins.
 */
class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency_code',
        'to_currency_code',
        'rate',
        'source',
        'fetched_at',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:10',
        'fetched_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
