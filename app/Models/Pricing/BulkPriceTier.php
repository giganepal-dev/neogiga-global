<?php

namespace App\Models\Pricing;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BulkPriceTier extends BaseModel
{
    protected $table = 'bulk_price_tiers';

    protected $fillable = [
        'priceable_type', 'priceable_id',
        'min_quantity', 'max_quantity', 'unit_price', 'discount_percentage',
        'currency_id', 'is_active', 'metadata'
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'unit_price' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Marketplace\Currency::class);
    }
}
