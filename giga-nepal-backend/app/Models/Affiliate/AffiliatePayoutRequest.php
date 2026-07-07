<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayoutRequest extends Model
{
    protected $fillable = [
        'affiliate_id', 'batch_id', 'amount', 'currency', 'status',
        'method', 'details', 'admin_note', 'requested_at', 'approved_at', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'details' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }
}
