<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductGenericSuggestion extends Model
{
    protected $fillable = [
        'product_id',
        'suggestion_type',
        'suggested_product_id',
        'reason',
        'confidence_score',
        'metadata',
        'is_active',
        'priority',
    ];

    const TYPE_ALTERNATIVE = 'alternative';
    const TYPE_UPGRADE = 'upgrade';
    const TYPE_ACCESSORY = 'accessory';
    const TYPE_COMPATIBLE = 'compatible';

    public static function getSuggestionTypes(): array
    {
        return [
            self::TYPE_ALTERNATIVE,
            self::TYPE_UPGRADE,
            self::TYPE_ACCESSORY,
            self::TYPE_COMPATIBLE,
        ];
    }

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function suggestedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'suggested_product_id');
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('suggestion_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority')
                    ->orderByDesc('confidence_score');
    }
}
