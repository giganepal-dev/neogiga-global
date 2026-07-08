<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only wallet ledger. Rows are never updated/deleted; each balance
 * change is a new signed entry carrying the resulting balance_after.
 */
class WalletLedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'wallet_id', 'type', 'amount', 'balance_after', 'order_id', 'reference', 'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
