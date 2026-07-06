<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPosInvoice extends Model
{
    protected $fillable = [
        'ai_session_id',
        'pos_sale_id',
        'marketplace_id',
        'vendor_id',
        'total_amount',
        'currency_code',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Vendor::class, 'vendor_id');
    }
}
