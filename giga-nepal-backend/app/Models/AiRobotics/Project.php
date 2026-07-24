<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'ai_robotics_projects';

    protected $fillable = [
        'name', 'slug', 'user_id', 'description', 'image', 'images',
        'tags', 'robot_model_ids', 'product_ids', 'difficulty',
        'status', 'is_featured', 'seo_meta',
    ];

    protected $casts = [
        'images' => 'array', 'tags' => 'array',
        'robot_model_ids' => 'array', 'product_ids' => 'array',
        'seo_meta' => 'array', 'is_featured' => 'boolean',
    ];

    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function scopePublished($q) { return $q->where('status', 'published'); }
}
