<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqAward extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'awarded_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(RfqBid::class, 'bid_id');
    }
}
