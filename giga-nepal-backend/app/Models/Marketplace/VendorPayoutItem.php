<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayoutItem extends Model
{
    protected $fillable = [
        'vendor_payout_id',
        'vendor_order_id',
        'description',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class, 'vendor_payout_id');
    }
}
