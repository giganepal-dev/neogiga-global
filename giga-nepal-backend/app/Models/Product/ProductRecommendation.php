<?php

namespace App\Models\Product;

use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecommendation extends Model
{
    protected $fillable = [
        'product_id', 'recommended_product_id', 'recommendation_type', 'score', 'explanation', 'metadata', 'is_active',
    ];

    protected $casts = ['score' => 'decimal:4', 'metadata' => 'array', 'is_active' => 'boolean'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function recommendedProduct(): BelongsTo { return $this->belongsTo(Product::class, 'recommended_product_id'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOfType($q, string $type) { return $q->where('recommendation_type', $type); }
    public function scopeTop($q, int $limit = 10) { return $q->orderByDesc('score')->limit($limit); }
}
