<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiModel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'provider', 'model_type', 'supported_tasks',
        'license_type', 'license_name', 'input_types', 'output_types',
        'hardware_requirements', 'supported_accelerators',
        'edge_compatible', 'cloud_compatible',
        'robotics_use_cases', 'cv_use_cases', 'nlp_use_cases',
        'documentation_url', 'download_url', 'github_url',
        'description', 'image', 'is_active', 'is_featured', 'seo_meta',
    ];

    protected $casts = [
        'supported_tasks' => 'array', 'input_types' => 'array', 'output_types' => 'array',
        'hardware_requirements' => 'array', 'supported_accelerators' => 'array',
        'robotics_use_cases' => 'array', 'cv_use_cases' => 'array', 'nlp_use_cases' => 'array',
        'seo_meta' => 'array',
        'edge_compatible' => 'boolean', 'cloud_compatible' => 'boolean',
        'is_active' => 'boolean', 'is_featured' => 'boolean',
    ];

    public function hardware(): BelongsToMany { return $this->belongsToMany(\App\Models\Marketplace\Product::class, 'ai_model_hardware'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
}
