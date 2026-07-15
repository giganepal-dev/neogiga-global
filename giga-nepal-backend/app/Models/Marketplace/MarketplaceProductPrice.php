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
        'product_variant_id',
        'base_price',
        'sale_price',
        'cost_price',
        'currency_code',
        'is_tax_inclusive',
        'tax_rate',
        'sale_start_date',
        'sale_end_date',
        'is_active',
        'supplier_product_offer_id',
        'source_name',
        'source_url',
        'source_file',
        'source_page_url',
        'downloaded_at',
        'imported_at',
        'data_year',
        'license_note',
        'confidence_level',
        'original_raw_value',
        'normalized_value',
        'pricing_rule',
        'source_review_status',
    ];

    protected $casts = [
        'base_price' => 'decimal:8',
        'sale_price' => 'decimal:8',
        'cost_price' => 'decimal:8',
        'is_tax_inclusive' => 'boolean',
        'tax_rate' => 'decimal:2',
        'sale_start_date' => 'date',
        'sale_end_date' => 'date',
        'is_active' => 'boolean',
        'downloaded_at' => 'datetime',
        'imported_at' => 'datetime',
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
