<?php

namespace App\Models\Marketplace;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
        'logo_original_url',
        'logo_source_domain',
        'logo_source_type',
        'logo_verified',
        'logo_verified_at',
        'logo_verified_by',
        'logo_alt_text',
        'logo_width',
        'logo_height',
        'logo_mime_type',
        'logo_sha256',
        'logo_background_type',
        'logo_status',
        'logo_review_note',
        'logo_confidence',
        'logo_metadata',
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
        'logo_verified' => 'boolean',
        'logo_verified_at' => 'datetime',
        'logo_confidence' => 'float',
        'logo_metadata' => 'array',
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

    public function logoHistory(): HasMany
    {
        return $this->hasMany(BrandLogoHistory::class, 'brand_id')->latest();
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(BrandAlias::class, 'brand_id');
    }

    public function verifiedLogoUrl(): ?string
    {
        if (! $this->logo_path || ! $this->logo_verified) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return null;
        }

        $disk = Storage::disk((string) config('brand_logos.disk', 'public'));
        if (! $disk->exists($this->logo_path)) {
            return null;
        }

        return $disk->url($this->logo_path);
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
