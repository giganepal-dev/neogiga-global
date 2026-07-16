<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAlias extends Model
{
    use HasFactory;

    protected $fillable = ['brand_id', 'alias', 'normalized_alias', 'source', 'confidence'];

    protected $casts = ['confidence' => 'float'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }
}
