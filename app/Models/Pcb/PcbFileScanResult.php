<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbFileScanResult extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'file_id',
        'scanner_name',
        'scanner_version',
        'result',
        'threat_name',
        'scan_log',
        'scan_duration_ms',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'scan_duration_ms' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($result) {
            if (empty($result->id)) {
                $result->id = (string) Str::uuid();
            }
            if (empty($result->scanned_at)) {
                $result->scanned_at = now();
            }
        });
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function isClean(): bool
    {
        return $this->result === 'clean';
    }

    public function isInfected(): bool
    {
        return in_array($this->result, ['infected', 'suspicious']);
    }
}
