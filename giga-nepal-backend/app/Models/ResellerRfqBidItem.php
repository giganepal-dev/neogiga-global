<?php

namespace App\Models;

use App\Models\Erp\RfqItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerRfqBidItem extends Model
{
    protected $fillable = [
        'bid_id', 'rfq_item_id', 'unit_price', 'quantity', 'total_price',
        'stock_status', 'substitute_mpn', 'item_notes',
    ];

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class, 'rfq_item_id');
    }
}
