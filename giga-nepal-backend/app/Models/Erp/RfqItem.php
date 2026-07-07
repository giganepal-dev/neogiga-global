<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqItem extends Model
{
    protected $fillable = [
        'rfq_request_id', 'product_id', 'sku', 'name', 'quantity', 'target_price', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'target_price' => 'decimal:4',
    ];

    public function rfqRequest(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class);
    }
}
