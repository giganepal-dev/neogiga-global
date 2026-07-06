<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends BaseModel
{
    protected $table = 'return_items';

    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'product_id',
        'quantity',
        'reason',
        'condition', // new, used, damaged
        'status', // pending, approved, received, processed
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product\Product::class);
    }
}
