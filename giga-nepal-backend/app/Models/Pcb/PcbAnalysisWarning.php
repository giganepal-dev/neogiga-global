<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbAnalysisWarning extends Model
{
    protected $fillable = [
        'analysis_run_id', 'severity', 'warning_code',
        'message', 'details', 'resolved', 'resolved_by_id',
        'resolved_at', 'resolution_notes',
    ];

    protected $casts = [
        'details' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(PcbGerberAnalysisRun::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'resolved_by_id');
    }
}
