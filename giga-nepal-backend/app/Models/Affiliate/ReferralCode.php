<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralCode extends Model
{
    protected $fillable = [
        'affiliate_id', 'code', 'landing_url', 'is_active',
        'click_count', 'signup_count', 'order_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class);
    }
}
