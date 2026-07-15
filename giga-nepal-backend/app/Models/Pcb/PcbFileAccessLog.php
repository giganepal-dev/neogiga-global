<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbFileAccessLog extends Model
{
    protected $fillable = [
        'file_id', 'user_id', 'action', 'ip_address',
        'user_agent', 'reason',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }
}
