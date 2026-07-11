<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbDetectedDimension extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'analysis_run_id',
        'width_mm',
        'height_mm',
        'area_mm2',
        'detected_layers_count',
        'minimum_track_mm',
        'minimum_spacing_mm',
        'minimum_drill_mm',
        'hole_count',
        'slot_count',
        'has_castellated_holes',
        'has_edge_plating',
        'is_panelized',
        'confidence_level',
        'raw_measurements',
    ];

    protected $casts = [
        'width_mm' => 'decimal:4',
        'height_mm' => 'decimal:4',
        'area_mm2' => 'decimal:4',
        'detected_layers_count' => 'integer',
        'minimum_track_mm' => 'decimal:4',
        'minimum_spacing_mm' => 'decimal:4',
        'minimum_drill_mm' => 'decimal:4',
        'hole_count' => 'integer',
        'slot_count' => 'integer',
        'has_castellated_holes' => 'boolean',
        'has_edge_plating' => 'boolean',
        'is_panelized' => 'boolean',
        'raw_measurements' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dimension) {
            if (empty($dimension->id)) {
                $dimension->id = (string) Str::uuid();
            }
        });
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(PcbFileAnalysisRun::class);
    }
}
