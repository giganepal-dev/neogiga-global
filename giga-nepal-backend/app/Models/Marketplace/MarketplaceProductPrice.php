<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductPrice extends Model
{
    protected $fillable = [
        'marketplace_id',
        'product_id',
        'variant_id',
        'base_price',
        'sale_price',
        'cost_price',
        'currency_code',
        'is_tax_inclusive',
        'tax_rate',
        'sale_start_date',
        'sale_end_date',
        'is_active',
        'source_name',
        'source_url',
        'source_offer_id',
        'source_fetched_at',
        'source_unit_price',
        'pricing_rule',
        'source_review_status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_tax_inclusive' => 'boolean',
        'tax_rate' => 'decimal:2',
        'sale_start_date' => 'date',
        'sale_end_date' => 'date',
        'is_active' => 'boolean',
        'source_fetched_at' => 'datetime',
        'source_unit_price' => 'decimal:6',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
