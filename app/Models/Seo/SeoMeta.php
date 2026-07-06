<?php

namespace App\Models\Seo;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends BaseModel
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'metaable_type',
        'metaable_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_markup',
        'hreflang_tags',
        'is_indexed',
    ];

    protected $casts = [
        'hreflang_tags' => 'array',
        'schema_markup' => 'array',
        'is_indexed' => 'boolean',
    ];

    public function metaable(): MorphTo
    {
        return $this->morphTo();
    }
}
