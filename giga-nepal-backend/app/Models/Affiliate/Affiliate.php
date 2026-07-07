<?php

namespace App\Models\Affiliate;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id', 'vendor_id', 'display_name', 'email', 'status',
        'country_id', 'default_currency', 'payout_method', 'payout_details',
        'total_earned', 'total_paid', 'meta', 'approved_at',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'meta' => 'array',
        'total_earned' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ReferralCode::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CommissionLedgerEntry::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(AffiliatePayoutRequest::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /** Approved-but-unpaid commission balance (server-computed, never trusted from client). */
    public function pendingBalance(): float
    {
        return (float) $this->commissions()->where('status', 'approved')->sum('commission_amount');
    }
}
