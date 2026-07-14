<?php

namespace App\Models;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'logo_path',
        'country_of_origin',
        'official_website',
        'overview',
        'source_name',
        'source_url',
        'source_file',
        'source_page_url',
        'downloaded_at',
        'imported_at',
        'data_year',
        'license_note',
        'confidence_level',
        'original_raw_value',
        'normalized_value',
        'last_verified_at',
        'seo_title',
        'seo_description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'imported_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function aliases(): HasMany
    {
        return $this->hasMany(ManufacturerAlias::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(ProductBrand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
