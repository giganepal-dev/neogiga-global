<?php

namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends LmsModel
{
    protected $table = 'lms_lessons';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'lms_course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'lms_module_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'lms_project_id');
    }
}
