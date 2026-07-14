<?php

namespace App\Models\Marketplace;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProductBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'logo_path',
        'banner_path',
        'website_url',
        'country_id',
        'manufacturer_id',
        'is_active',
        'is_featured',
        'sort_order',
        'marketplace_visibility',
        'country_visibility',
        'category_visibility',
        'menu_placement',
        'publication_starts_at',
        'publication_ends_at',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'is_menu_visible',
        'display_desktop',
        'display_mobile',
        'hide_when_unavailable',
        'landing_page_enabled',
        'seo_meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'marketplace_visibility' => 'array',
        'country_visibility' => 'array',
        'category_visibility' => 'array',
        'publication_starts_at' => 'datetime',
        'publication_ends_at' => 'datetime',
        'is_menu_visible' => 'boolean',
        'display_desktop' => 'boolean',
        'display_mobile' => 'boolean',
        'hide_when_unavailable' => 'boolean',
        'landing_page_enabled' => 'boolean',
        'seo_meta' => 'array',
    ];

    protected static function booted(): void
    {
        $invalidate = static function (): void {
            Cache::forever('catalog:brand-version', (string) now()->getTimestampMs());
            Cache::forever('seo:sitemap-version', (string) now()->getTimestampMs());
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
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
