<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSpecGroup extends Model
{
    protected $fillable = [
        'product_id',
        'category_id',
        'name',
        'sort_order',
        'is_active',
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
