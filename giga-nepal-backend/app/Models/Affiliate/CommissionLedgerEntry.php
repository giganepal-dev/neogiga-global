<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only in intent: `order_total_snapshot` and `commission_amount`
 * are set once at creation and never mutated. Corrections are made by
 * inserting a reversing entry, not by editing an existing row. Only the
 * `status` field and payout linkage transition over the lifecycle.
 */
class CommissionLedgerEntry extends Model
{
    protected $table = 'commission_ledger';

    protected $fillable = [
        'affiliate_id', 'order_id', 'referral_attribution_id', 'commission_rule_id',
        'payout_request_id', 'currency', 'order_total_snapshot', 'commission_amount',
        'status', 'reason', 'country_id', 'meta', 'approved_at', 'reversed_at', 'paid_at',
    ];

    protected $casts = [
        'order_total_snapshot' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'meta' => 'array',
        'approved_at' => 'datetime',
        'reversed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }
}
