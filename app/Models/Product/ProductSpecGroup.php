<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecGroup extends BaseModel
{
    protected $table = 'product_spec_groups';

    protected $fillable = [
        'product_id', 'name', 'sort_order', 'is_visible', 'metadata'
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

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class, 'spec_group_id');
    }
}
