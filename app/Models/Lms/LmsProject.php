<?php

namespace App\Models\Lms;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Product\Product;

class LmsProject extends BaseModel
{
    protected $table = 'lms_projects';

    protected $fillable = [
        'lms_course_id',
        'lms_lesson_id',
        'title',
        'slug',
        'description',
        'difficulty_level',
        'estimated_time_hours',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'estimated_time_hours' => 'decimal:2',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lms_lesson_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(LmsProjectComponent::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'lms_product_links')
                    ->withPivot('quantity', 'is_required')
                    ->withTimestamps();
    }

    public function codeSamples(): HasMany
    {
        return $this->hasMany(LmsCodeSample::class);
    }
}
