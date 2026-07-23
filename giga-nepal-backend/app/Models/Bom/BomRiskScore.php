<?php

namespace App\Models\Bom;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomRiskScore extends Model
{
    protected $fillable = [
        'bom_import_id',
        'bom_import_line_id',
        'risk_score',
        'risk_level',
        'risk_factors',
        'mitigation_suggestions',
        'needs_review',
        'metadata',
    ];

    protected $casts = [
        'risk_factors' => 'array',
        'mitigation_suggestions' => 'array',
        'needs_review' => 'boolean',
        'metadata' => 'array',
    ];

    public function bomImport(): BelongsTo
    {
        return $this->belongsTo(BomImport::class);
    }

    public function bomImportLine(): BelongsTo
    {
        return $this->belongsTo(BomImportLine::class);
    }

    /**
     * Scope by risk level.
     */
    public function scopeOfLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    /**
     * Scope to items needing review.
     */
    public function scopeNeedingReview($query)
    {
        return $query->where('needs_review', true);
    }

    /**
     * Scope to high/critical risk items.
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    /**
     * Get risk level color for UI.
     */
    public function getRiskColorAttribute(): string
    {
        return match ($this->risk_level) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }
}
