<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningPath extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'level', 'description', 'image',
        'estimated_hours', 'prerequisites', 'is_active', 'is_featured', 'seo_meta',
    ];

    protected $casts = [
        'prerequisites' => 'array', 'seo_meta' => 'array',
        'is_active' => 'boolean', 'is_featured' => 'boolean', 'estimated_hours' => 'integer',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Lms\Course::class, 'learning_path_course')
            ->withPivot(['sort_order', 'is_required'])
            ->orderByPivot('sort_order');
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
}
