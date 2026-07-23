<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsModalCampaign extends Model
{
    protected $fillable = [
        'news_post_id', 'title', 'description', 'image_url', 'cta_text', 'target_url',
        'start_date', 'end_date', 'regional_targeting', 'audience_targeting',
        'frequency', 'is_active', 'view_count', 'click_count', 'metadata',
    ];

    protected $casts = [
        'regional_targeting' => 'array', 'audience_targeting' => 'array',
        'metadata' => 'array', 'is_active' => 'boolean',
        'view_count' => 'integer', 'click_count' => 'integer',
        'start_date' => 'datetime', 'end_date' => 'datetime',
    ];

    public function post(): BelongsTo { return $this->belongsTo(NewsPost::class, 'news_post_id'); }

    public function scopeActive($q) {
        return $q->where('is_active', true)
            ->where(function ($w) {
                $w->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($w) {
                $w->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function incrementView(): void { $this->increment('view_count'); }
    public function incrementClick(): void { $this->increment('click_count'); }
}
