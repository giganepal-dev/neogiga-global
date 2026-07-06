<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends BaseModel
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id', 'sku', 'name', 'description',
        'price', 'compare_at_price', 'cost_price',
        'weight', 'length', 'width', 'height',
        'is_default', 'sort_order', 'metadata'
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'compare_at_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\InventoryStock::class);
    }

    public function marketplacePrices(): HasMany
    {
        return $this->hasMany(\App\Models\Pricing\MarketplaceProductPrice::class);
    }
}
