<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'currency', 'scope', 'applies_to',
        'min_order_total', 'max_discount', 'usage_limit', 'usage_limit_per_user',
        'used_count', 'marketplace_id', 'starts_at', 'ends_at', 'is_active', 'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'applies_to' => 'array',
        'min_order_total' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isLiveNow(): bool
    {
        $now = now();

        return $this->is_active
            && (!$this->starts_at || $this->starts_at->lte($now))
            && (!$this->ends_at || $this->ends_at->gte($now));
    }
}
