<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $table = 'ai_robotics_articles';

    protected $fillable = [
        'title', 'slug', 'article_type', 'excerpt', 'body', 'featured_image',
        'author_id', 'tags', 'related_product_ids', 'related_course_ids',
        'related_robot_model_ids', 'status', 'published_at', 'is_featured', 'seo_meta',
    ];

    protected $casts = [
        'tags' => 'array', 'related_product_ids' => 'array',
        'related_course_ids' => 'array', 'related_robot_model_ids' => 'array',
        'seo_meta' => 'array', 'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    public function author() { return $this->belongsTo(\App\Models\User::class, 'author_id'); }
    public function scopePublished($q) { return $q->where('status', 'published')->whereNotNull('published_at'); }
    public function scopeActive($q) { return $q->where('status', 'published'); }
}
