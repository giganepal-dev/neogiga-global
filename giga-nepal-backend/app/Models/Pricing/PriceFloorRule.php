<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class PriceFloorRule extends Model
{
    protected $fillable = [
        'marketplace_id', 'scope_type', 'scope_id',
        'min_absolute_price', 'currency_code',
        'max_discount_percent', 'max_fixed_discount', 'is_active',
    ];

    protected $casts = [
        'min_absolute_price' => 'decimal:6',
        'max_discount_percent' => 'decimal:2',
        'max_fixed_discount' => 'decimal:6',
        'is_active' => 'boolean',
    ];
}
