<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosZReport extends Model
{
    protected $table = 'pos_z_reports';
    protected $guarded = [];
    protected $casts = ['report_date' => 'datetime', 'payment_breakdown' => 'array'];

    public function register(): BelongsTo { return $this->belongsTo(PosRegister::class, 'register_id'); }
    public function shift(): BelongsTo { return $this->belongsTo(PosShift::class, 'shift_id'); }
    public function closedBy(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
}
