<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use App\Models\Pos\PosSale;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPosInvoice extends BaseModel
{
    protected $table = 'ai_pos_invoices';

    protected $fillable = [
        'ai_session_id',
        'pos_sale_id',
        'status',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }
}
