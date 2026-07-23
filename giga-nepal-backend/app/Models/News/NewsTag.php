<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NewsTag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function posts(): BelongsToMany { return $this->belongsToMany(NewsPost::class, 'news_post_tag'); }
}
