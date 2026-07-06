<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpec extends Model
{
    protected $fillable = [
        'product_id',
        'spec_group_id',
        'name',
        'value',
        'unit',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function specGroup(): BelongsTo
    {
        return $this->belongsTo(ProductSpecGroup::class, 'spec_group_id');
    }
}
