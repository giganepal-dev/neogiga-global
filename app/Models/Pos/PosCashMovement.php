<?php

namespace App\Models\Pos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCashMovement extends BaseModel
{
    protected $table = 'pos_cash_movements';

    protected $fillable = [
        'pos_session_id',
        'movement_type', // in, out
        'amount',
        'reason',
        'reference_number',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }
}
