<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use App\Models\Lms\LmsProject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLmsRecommendation extends BaseModel
{
    protected $table = 'ai_lms_recommendations';

    protected $fillable = [
        'ai_session_id',
        'lms_project_id',
        'relevance_score',
        'reason',
        'meta_data',
    ];

    protected $casts = [
        'relevance_score' => 'decimal:4',
        'meta_data' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function lmsProject(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class);
    }
}
