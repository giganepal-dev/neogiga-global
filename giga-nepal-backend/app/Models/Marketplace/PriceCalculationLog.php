<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceCalculationLog extends Model
{
    protected $fillable = [
        'product_id',
        'marketplace_id',
        'base_cost_usd',
        'exchange_rate',
        'duty_amount',
        'tax_amount',
        'freight_amount',
        'margin_amount',
        'final_price',
        'currency_code',
        'calculation_version',
    ];

    protected $casts = [
        'base_cost_usd' => 'decimal:4',
        'exchange_rate' => 'decimal:10',
        'duty_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'freight_amount' => 'decimal:4',
        'margin_amount' => 'decimal:4',
        'final_price' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }
}
