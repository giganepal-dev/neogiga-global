<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiBomItem extends Model
{
    protected $fillable = [
        'ai_bom_build_id',
        'product_id',
        'product_name',
        'quantity',
        'estimated_price',
        'currency_code',
        'reason',
        'category',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_price' => 'decimal:2',
    ];

    public function bomBuild(): BelongsTo
    {
        return $this->belongsTo(AiBomBuild::class, 'ai_bom_build_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Product::class, 'product_id');
    }
}
