<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorySynonym extends Model
{
    protected $fillable = ['category_id', 'synonym', 'normalized_synonym', 'source', 'confidence'];

    protected $casts = ['confidence' => 'float'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
