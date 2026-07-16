<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandLogoHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id', 'action', 'storage_disk', 'logo_path', 'original_url', 'source_domain',
        'source_type', 'confidence', 'status', 'review_note', 'metadata', 'created_by',
    ];

    protected $casts = [
        'confidence' => 'float',
        'metadata' => 'array',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }
}
