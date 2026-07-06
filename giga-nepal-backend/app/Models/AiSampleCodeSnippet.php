<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSampleCodeSnippet extends Model
{
    protected $fillable = [
        'ai_session_id',
        'lms_project_id',
        'product_id',
        'language',
        'title',
        'description',
        'code_content',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function lmsProject(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class, 'lms_project_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Product::class, 'product_id');
    }
}
