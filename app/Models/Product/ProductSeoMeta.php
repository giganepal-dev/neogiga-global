<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSeoMeta extends BaseModel
{
    protected $table = 'product_seo_meta';

    protected $fillable = [
        'product_id', 'meta_title', 'meta_description', 'meta_keywords',
        'canonical_url', 'og_title', 'og_description', 'og_image_path',
        'twitter_title', 'twitter_description', 'twitter_card_type',
        'schema_markup', 'metadata'
    ];

    protected $casts = [
        'schema_markup' => 'array',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
