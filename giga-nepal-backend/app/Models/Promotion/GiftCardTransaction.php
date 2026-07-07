<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only gift-card ledger. Rows are never updated or deleted; balance
 * changes are expressed as new signed entries with the resulting balance_after.
 */
class GiftCardTransaction extends Model
{
    public const UPDATED_AT = null; // append-only: no updated_at

    protected $fillable = [
        'gift_card_id', 'type', 'amount', 'balance_after', 'order_id', 'user_id', 'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }
}
