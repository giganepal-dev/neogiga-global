<?php

namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends LmsModel
{
    protected $table = 'lms_modules';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'lms_course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'lms_module_id')->orderBy('sort_order');
    }
}
