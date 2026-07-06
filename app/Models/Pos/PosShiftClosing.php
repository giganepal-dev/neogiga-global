<?php

namespace App\Models\Pos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosShiftClosing extends BaseModel
{
    protected $table = 'pos_shift_closings';

    protected $fillable = [
        'pos_session_id',
        'expected_cash',
        'actual_cash',
        'difference',
        'closing_notes',
        'closed_by',
        'status', // balanced, over, short
    ];

    protected $casts = [
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }
}
