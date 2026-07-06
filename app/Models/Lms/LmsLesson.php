<?php

namespace App\Models\Lms;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsLesson extends BaseModel
{
    protected $table = 'lms_lessons';

    protected $fillable = [
        'lms_course_id',
        'title',
        'slug',
        'content',
        'order',
        'duration_minutes',
        'video_url',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(LmsProject::class);
    }
}
