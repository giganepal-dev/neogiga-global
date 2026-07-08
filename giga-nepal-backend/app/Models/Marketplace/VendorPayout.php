<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayout extends Model
{
    protected $fillable = [
        'vendor_id',
        'payout_number',
        'status',
        'currency_code',
        'gross_amount',
        'commission_amount',
        'fee_amount',
        'net_amount',
        'payout_method_id',
        'approved_at',
        'paid_at',
        'marked_paid_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorPayoutItem::class);
    }
}
