<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosRefund extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'pos_payment_id',
        'amount',
        'currency_code',
        'reason',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function posPayment(): BelongsTo
    {
        return $this->belongsTo(PosPayment::class, 'pos_payment_id');
    }
}
