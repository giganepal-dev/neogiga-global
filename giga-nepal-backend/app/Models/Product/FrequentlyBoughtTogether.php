<?php

namespace App\Models\Product;

use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrequentlyBoughtTogether extends Model
{
    protected $fillable = [
        'product_id', 'companion_product_id', 'co_occurrence_count', 'confidence', 'lift', 'source', 'metadata', 'is_active',
    ];

    protected $casts = [
        'co_occurrence_count' => 'integer', 'confidence' => 'decimal:4',
        'lift' => 'decimal:4', 'metadata' => 'array', 'is_active' => 'boolean',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function companionProduct(): BelongsTo { return $this->belongsTo(Product::class, 'companion_product_id'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeTop($q, int $limit = 10) { return $q->orderByDesc('confidence')->limit($limit); }
}
