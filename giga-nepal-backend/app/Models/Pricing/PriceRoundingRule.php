<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class PriceRoundingRule extends Model
{
    protected $fillable = [
        'marketplace_id', 'currency_code', 'strategy', 'increment', 'charm_ending', 'is_active',
    ];

    protected $casts = [
        'increment' => 'decimal:4',
        'charm_ending' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
