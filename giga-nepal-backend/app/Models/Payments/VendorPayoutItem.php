<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayoutItem extends Model
{
    protected $fillable = ['vendor_payout_id', 'order_id', 'description', 'amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class, 'vendor_payout_id');
    }
}
