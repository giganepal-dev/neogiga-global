<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecification extends Model
{
    protected $fillable = [
        'product_id',
        'template_field_id',
        'value',
        'unit_override',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function templateField(): BelongsTo
    {
        return $this->belongsTo(SpecTemplateField::class, 'template_field_id');
    }

    public function getUnitAttribute(): ?string
    {
        return $this->unit_override ?? $this->templateField?->unit;
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
