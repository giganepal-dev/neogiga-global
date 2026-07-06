<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideo extends BaseModel
{
    protected $table = 'product_videos';

    protected $fillable = [
        'product_id', 'title', 'video_path', 'thumbnail_path',
        'video_type', 'duration', 'sort_order', 'is_visible', 'metadata'
    ];

    protected $casts = [
        'duration' => 'integer',
        'sort_order' => 'integer',
        'is_visible' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
