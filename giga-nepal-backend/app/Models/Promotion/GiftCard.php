<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCard extends Model
{
    protected $fillable = [
        'code', 'initial_balance', 'current_balance', 'currency', 'status',
        'issued_to_email', 'user_id', 'expires_at', 'meta',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class);
    }

    public function isSpendable(): bool
    {
        return $this->status === 'active'
            && (float) $this->current_balance > 0
            && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
