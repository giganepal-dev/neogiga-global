<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'condition',
        'reason',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\ProductVariant::class, 'product_variant_id');
    }
}
