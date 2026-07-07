<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralAttribution extends Model
{
    protected $fillable = [
        'referral_code_id', 'affiliate_id', 'visitor_token', 'user_id',
        'utm_source', 'utm_medium', 'utm_campaign', 'source_url',
        'ip_hash', 'user_agent_hash', 'status', 'converted_order_id',
        'attributed_at', 'converted_at',
    ];

    protected $casts = [
        'attributed_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }
}
