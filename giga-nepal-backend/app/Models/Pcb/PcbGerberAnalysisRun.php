<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PcbGerberAnalysisRun extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Str::uuid();
            }
        });
    }

    protected $fillable = [
        'project_id', 'file_id', 'triggered_by_id',
        'parser_version', 'status', 'error_message',
        'detected_width_mm', 'detected_height_mm',
        'detected_layer_count', 'detected_min_trace_mm',
        'detected_min_spacing_mm', 'detected_min_drill_mm',
        'detected_hole_count', 'detected_slot_count',
        'detected_board_area_cm2', 'detected_copper_area_percent',
        'has_castellated_indicator', 'has_edge_plating_indicator',
        'has_panelization_indicator',
        'confidence_level', 'engineering_reviewed',
        'reviewed_by_id', 'reviewed_at',
    ];

    protected $casts = [
        'detected_width_mm' => 'decimal:4',
        'detected_height_mm' => 'decimal:4',
        'detected_layer_count' => 'integer',
        'detected_min_trace_mm' => 'decimal:4',
        'detected_min_spacing_mm' => 'decimal:4',
        'detected_min_drill_mm' => 'decimal:4',
        'detected_hole_count' => 'integer',
        'detected_slot_count' => 'integer',
        'detected_board_area_cm2' => 'decimal:4',
        'detected_copper_area_percent' => 'decimal:2',
        'has_castellated_indicator' => 'boolean',
        'has_edge_plating_indicator' => 'boolean',
        'has_panelization_indicator' => 'boolean',
        'engineering_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class, 'project_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'triggered_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'reviewed_by_id');
    }

    public function detectedLayers(): HasMany
    {
        return $this->hasMany(PcbDetectedLayer::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(PcbAnalysisWarning::class);
    }
}
