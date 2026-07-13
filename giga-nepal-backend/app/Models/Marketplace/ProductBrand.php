<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'logo_path',
        'banner_path',
        'website_url',
        'country_id',
        'is_active',
        'is_featured',
        'is_menu_visible',
        'display_desktop',
        'display_mobile',
        'hide_when_unavailable',
        'landing_page_enabled',
        'sort_order',
        'menu_placement',
        'publication_starts_at',
        'publication_ends_at',
        'marketplace_visibility',
        'country_visibility',
        'category_visibility',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'seo_meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_menu_visible' => 'boolean',
        'display_desktop' => 'boolean',
        'display_mobile' => 'boolean',
        'hide_when_unavailable' => 'boolean',
        'landing_page_enabled' => 'boolean',
        'sort_order' => 'integer',
        'publication_starts_at' => 'datetime',
        'publication_ends_at' => 'datetime',
        'marketplace_visibility' => 'array',
        'country_visibility' => 'array',
        'category_visibility' => 'array',
        'seo_meta' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
