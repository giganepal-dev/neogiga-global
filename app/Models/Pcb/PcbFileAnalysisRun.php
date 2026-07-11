<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PcbFileAnalysisRun extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'file_id',
        'analysis_type',
        'parser_version',
        'status',
        'configuration',
        'results',
        'error_message',
        'duration_ms',
        'triggered_by_id',
    ];

    protected $casts = [
        'configuration' => 'array',
        'results' => 'array',
        'duration_ms' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($run) {
            if (empty($run->id)) {
                $run->id = (string) Str::uuid();
            }
        });
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function triggerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }

    public function detectedLayers(): HasMany
    {
        return $this->hasMany(PcbDetectedLayer::class);
    }

    public function detectedDimensions(): HasMany
    {
        return $this->hasMany(PcbDetectedDimension::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
