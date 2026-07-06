<?php

namespace App\Models\Lms;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsCourse extends BaseModel
{
    protected $table = 'lms_courses';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'skill_level_id',
        'duration_hours',
        'is_published',
        'thumbnail',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'duration_hours' => 'decimal:2',
    ];

    public function skillLevel()
    {
        return $this->belongsTo(LmsSkillLevel::class, 'skill_level_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LmsLesson::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(LmsProject::class);
    }
}
