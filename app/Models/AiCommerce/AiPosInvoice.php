<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Pos\PosSale;

class AiPosInvoice extends BaseModel
{
    protected $table = 'ai_pos_invoices';

    protected $fillable = [
        'ai_session_id',
        'pos_sale_id',
        'bom_description',
        'total_amount',
        'currency_code',
        'status', // draft, completed
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
}
