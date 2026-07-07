<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    protected $fillable = [
        'name', 'scope', 'scope_id', 'type', 'rate', 'currency',
        'min_order_total', 'max_commission', 'priority', 'is_active',
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'min_order_total' => 'decimal:2',
        'max_commission' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function isLiveNow(): bool
    {
        $now = now();

        return $this->is_active
            && (!$this->starts_at || $this->starts_at->lte($now))
            && (!$this->ends_at || $this->ends_at->gte($now));
    }
}
