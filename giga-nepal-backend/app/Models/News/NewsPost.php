<?php

namespace App\Models\News;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'news_category_id', 'author_id', 'reviewer_id', 'title', 'slug', 'subtitle',
        'excerpt', 'body', 'post_type', 'status', 'is_featured', 'is_pinned',
        'published_at', 'scheduled_at', 'hero_image', 'og_image', 'tags',
        'downloads', 'sources', 'related_product_ids', 'related_category_ids',
        'regional_targeting', 'language_targeting', 'meta_title', 'meta_description',
        'canonical_url', 'view_count', 'share_count', 'comments_enabled',
        'add_to_modal', 'metadata',
    ];

    protected $casts = [
        'tags' => 'array', 'downloads' => 'array', 'sources' => 'array',
        'related_product_ids' => 'array', 'related_category_ids' => 'array',
        'regional_targeting' => 'array', 'language_targeting' => 'array',
        'metadata' => 'array', 'is_featured' => 'boolean', 'is_pinned' => 'boolean',
        'comments_enabled' => 'boolean', 'add_to_modal' => 'boolean',
        'view_count' => 'integer', 'share_count' => 'integer',
        'published_at' => 'datetime', 'scheduled_at' => 'datetime',
    ];

    public function category(): BelongsTo { return $this->belongsTo(NewsCategory::class, 'news_category_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function relatedPosts(): BelongsToMany { return $this->belongsToMany(NewsPost::class, 'news_post_relations', 'news_post_id', 'related_post_id'); }
    public function tagsRel(): BelongsToMany { return $this->belongsToMany(NewsTag::class, 'news_post_tag'); }

    public function scopePublished($q) { return $q->where('status', 'published')->whereNotNull('published_at'); }
    public function scopeDraft($q) { return $q->where('status', 'draft'); }
    public function scopeScheduled($q) { return $q->where('status', 'scheduled'); }
    public function scopeOfType($q, string $type) { return $q->where('post_type', $type); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
    public function scopePinned($q) { return $q->where('is_pinned', true); }

    public function incrementView(): void { $this->increment('view_count'); }
    public function incrementShare(): void { $this->increment('share_count'); }

    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->body ?? ''));
        return max(1, (int) ceil($words / 200));
    }
}
