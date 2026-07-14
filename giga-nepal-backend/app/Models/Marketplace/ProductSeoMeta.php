<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product SEO keeps manual/locked active values separate from regenerated
 * suggestions. Append-only snapshots live in catalog_seo_versions.
 */
class ProductSeoMeta extends Model
{
    protected $table = 'product_seo_meta';

    protected $fillable = [
        'product_id',
        'title',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots',
        'schema_type',
        'schema_json',
        'confidence_level',
        'generated_title',
        'generated_description',
        'generated_canonical_url',
        'generated_robots',
        'robots_reason',
        'template_version',
        'is_manual_override',
        'is_locked',
        'active_source',
        'modified_by',
        'generated_at',
        'metadata',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'metadata' => 'array',
        'is_manual_override' => 'boolean',
        'is_locked' => 'boolean',
        'generated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
