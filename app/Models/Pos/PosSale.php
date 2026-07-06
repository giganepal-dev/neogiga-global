<?php

namespace App\Models\Pos;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends BaseModel
{
    protected $table = 'pos_sales';

    protected $fillable = [
        'pos_session_id',
        'sale_number',
        'user_id',
        'marketplace_id',
        'status',
        'currency_code',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'amount_paid',
        'amount_due',
        'customer_name',
        'customer_contact',
        'notes',
        'meta_data',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'meta_data' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PosRefund::class);
    }
}
