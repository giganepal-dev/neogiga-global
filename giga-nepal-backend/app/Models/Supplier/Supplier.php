<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'tier',
        'description',
        'website_url',
        'api_endpoint',
        'api_credentials',
        'logo_path',
        'country',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'api_credentials' => 'encrypted:array',
        'metadata' => 'array',
    ];

    public function productSuppliers(): HasMany
    {
        return $this->hasMany(ProductSupplier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTier1($query)
    {
        return $query->where('tier', 'tier_1');
    }

    public function scopeTier2($query)
    {
        return $query->where('tier', 'tier_2');
    }

    public function scopeTier3($query)
    {
        return $query->where('tier', 'tier_3');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
