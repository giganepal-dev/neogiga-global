<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompatibility extends BaseModel
{
    protected $table = 'product_compatibility';

    protected $fillable = [
        'product_id', 'compatible_product_id', 'compatibility_type',
        'notes', 'is_verified', 'metadata'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function compatibleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'compatible_product_id');
    }
}
