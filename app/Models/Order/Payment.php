<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends BaseModel
{
    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'payment_number',
        'amount',
        'currency_code',
        'payment_method', // card, bank_transfer, cash, wallet, etc.
        'payment_gateway',
        'transaction_id',
        'status', // pending, completed, failed, refunded
        'payment_date',
        'metadata',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
