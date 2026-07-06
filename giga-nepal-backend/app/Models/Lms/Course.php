<?php

namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends LmsModel
{
    protected $table = 'lms_courses';

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class, 'lms_course_id')->orderBy('sort_order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'lms_course_id')->orderBy('sort_order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'lms_course_id');
    }
}
