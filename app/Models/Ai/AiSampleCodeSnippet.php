<?php

namespace App\Models\Ai;

use App\Models\BaseModel;
use App\Models\Lms\LmsProject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSampleCodeSnippet extends BaseModel
{
    protected $table = 'ai_sample_code_snippets';

    protected $fillable = [
        'ai_session_id',
        'lms_project_id',
        'language',
        'title',
        'code',
        'description',
        'meta_data',
    ];

    protected $casts = [
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
