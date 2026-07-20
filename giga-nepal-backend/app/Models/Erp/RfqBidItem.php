<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqBidItem extends Model
{
    protected $guarded = [];

    public function bid(): BelongsTo
    {
        return $this->belongsTo(RfqBid::class, 'bid_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class, 'rfq_item_id');
    }
}
