<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayout extends Model
{
    protected $fillable = [
        'payout_number', 'vendor_id', 'currency', 'amount', 'status', 'method',
        'period_start', 'period_end', 'notes', 'created_by', 'approved_at', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(VendorPayoutItem::class);
    }
}
