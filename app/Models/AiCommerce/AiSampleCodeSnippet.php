<?php

namespace App\Models\AiCommerce;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Lms\LmsProject;

class AiSampleCodeSnippet extends BaseModel
{
    protected $table = 'ai_sample_code_snippets';

    protected $fillable = [
        'ai_session_id',
        'lms_project_id',
        'title',
        'language', // arduino, python, javascript, etc.
        'code_content',
        'description',
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
