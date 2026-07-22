<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosRegisterHistory extends Model
{
    protected $table = 'pos_register_history';
    protected $guarded = [];

    public function register(): BelongsTo { return $this->belongsTo(PosRegister::class, 'register_id'); }
    public function shift(): BelongsTo { return $this->belongsTo(PosShift::class, 'shift_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
