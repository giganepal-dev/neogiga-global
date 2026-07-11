<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbDetectedLayer extends Model
{
    protected $fillable = [
        'analysis_run_id', 'filename', 'detected_type',
        'expected_type', 'is_matched', 'layer_order', 'metadata',
    ];

    protected $casts = [
        'is_matched' => 'boolean',
        'layer_order' => 'integer',
        'metadata' => 'array',
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(PcbGerberAnalysisRun::class);
    }
}
