<?php

namespace App\Models\Lms;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsCodeSample extends BaseModel
{
    protected $table = 'lms_code_samples';

    protected $fillable = [
        'lms_project_id',
        'title',
        'language', // arduino, python, javascript, etc.
        'code_content',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class, 'lms_project_id');
    }
}
