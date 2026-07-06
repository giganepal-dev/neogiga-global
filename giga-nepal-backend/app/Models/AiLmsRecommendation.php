<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLmsRecommendation extends Model
{
    protected $fillable = [
        'ai_session_id',
        'lms_project_id',
        'relevance_score',
        'reason',
    ];

    protected $casts = [
        'relevance_score' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function lmsProject(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class, 'lms_project_id');
    }
}
