<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'type', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function posts(): HasMany { return $this->hasMany(NewsPost::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOfType($q, string $type) { return $q->where('type', $type); }
}
