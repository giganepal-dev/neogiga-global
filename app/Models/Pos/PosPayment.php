<?php

namespace App\Models\Pos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends BaseModel
{
    protected $table = 'pos_payments';

    protected $fillable = [
        'pos_sale_id',
        'method',
        'amount',
        'currency_code',
        'transaction_ref',
        'notes',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta_data' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }
}
