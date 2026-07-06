<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnOrder extends BaseModel
{
    protected $table = 'returns';

    protected $fillable = [
        'order_id',
        'return_number',
        'status',
        'reason',
        'notes',
        'customer_notes',
        'meta_data',
        'requested_at',
        'approved_at',
        'received_at',
        'completed_at',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
