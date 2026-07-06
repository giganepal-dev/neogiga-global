<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRequest extends BaseModel
{
    protected $table = 'return_requests';

    protected $fillable = [
        'order_id',
        'return_number',
        'status', // pending, approved, received, processed, completed, rejected
        'reason',
        'requested_by',
        'requested_at',
        'approved_at',
        'received_at',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }
}
