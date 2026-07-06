<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpec extends BaseModel
{
    protected $table = 'product_specs';

    protected $fillable = [
        'product_id', 'variant_id', 'spec_group_id', 'name', 'value',
        'unit', 'sort_order', 'is_visible', 'metadata'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_visible' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function specGroup(): BelongsTo
    {
        return $this->belongsTo(ProductSpecGroup::class, 'spec_group_id');
    }
}
