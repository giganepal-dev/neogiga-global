<?php

namespace App\Models\Pcb;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbFileAccessLog extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'file_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'organization_id',
        'access_reason',
        'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->id)) {
                $log->id = (string) Str::uuid();
            }
            if (empty($log->accessed_at)) {
                $log->accessed_at = now();
            }
        });
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
