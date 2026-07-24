<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionalPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'target_institution', 'description', 'short_description',
        'image', 'equipment_list', 'includes', 'base_price', 'currency',
        'is_active', 'is_featured', 'seo_meta',
    ];

    protected $casts = [
        'equipment_list' => 'array', 'includes' => 'array', 'seo_meta' => 'array',
        'base_price' => 'decimal:2', 'is_active' => 'boolean', 'is_featured' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Marketplace\Product::class, 'institutional_package_product')
            ->withPivot(['quantity', 'is_required', 'notes']);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
