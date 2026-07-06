<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Lms\LmsProject;

class AiLmsRecommendation extends BaseModel
{
    protected $table = 'ai_lms_recommendations';

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class, 'lms_project_id');
    }
}
