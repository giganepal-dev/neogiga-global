<?php

namespace App\Models\Pricing;

use App\Models\BaseModel;
use App\Models\Product\Product;
use App\Models\Vendor\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorProductPrice extends BaseModel
{
    protected $table = 'vendor_product_prices';

    protected $fillable = [
        'product_id', 'variant_id', 'vendor_id', 'currency_id',
        'base_price', 'sale_price', 'cost_price', 'min_price',
        'price_includes_tax', 'tax_rate', 'start_date', 'end_date',
        'is_active', 'metadata'
    ];

    protected $casts = [
        'base_price' => 'decimal:4',
        'sale_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'min_price' => 'decimal:4',
        'price_includes_tax' => 'boolean',
        'tax_rate' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(\App\Models\Product\ProductVariant::class, 'variant_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Marketplace\Currency::class);
    }

    public function bulkTiers(): HasMany
    {
        return $this->hasMany(BulkPriceTier::class, 'priceable_id')
            ->where('priceable_type', self::class);
    }
}
