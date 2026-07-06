<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaim extends BaseModel
{
    protected $table = 'warranty_claims';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'claim_number',
        'status', // pending, approved, in_progress, resolved, rejected
        'issue_description',
        'resolution',
        'claimed_by',
        'claimed_at',
        'resolved_at',
        'notes',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
