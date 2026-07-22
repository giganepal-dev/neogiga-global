<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbFileScanResult extends Model
{
    protected $fillable = [
        'file_id', 'scanner_name', 'scanner_version',
        'is_clean', 'threat_name', 'scan_details',
        'scan_duration_ms',
    ];

    protected $casts = [
        'is_clean' => 'boolean',
        'scan_details' => 'array',
        'scan_duration_ms' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class, 'file_id');
    }
}
