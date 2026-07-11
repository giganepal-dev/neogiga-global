<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbDetectedLayer extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'analysis_run_id',
        'layer_name',
        'detected_type',
        'side',
        'layer_order',
        'original_filename',
        'is_required',
        'is_present',
        'confidence_level',
        'metadata',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_present' => 'boolean',
        'layer_order' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($layer) {
            if (empty($layer->id)) {
                $layer->id = (string) Str::uuid();
            }
        });
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(PcbFileAnalysisRun::class);
    }
}
