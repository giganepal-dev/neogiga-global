<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWarranty extends Model
{
    protected $fillable = [
        'product_id',
        'warranty_type',
        'warranty_period_months',
        'warranty_coverage',
        'warranty_terms',
        'warranty_exclusions',
        'warranty_contact',
        'warranty_email',
        'warranty_phone',
        'is_international',
        'additional_info',
    ];

    const TYPE_MANUFACTURER = 'manufacturer';
    const TYPE_SELLER = 'seller';
    const TYPE_NONE = 'none';

    public static function getWarrantyTypes(): array
    {
        return [
            self::TYPE_MANUFACTURER,
            self::TYPE_SELLER,
            self::TYPE_NONE,
        ];
    }

    protected $casts = [
        'warranty_period_months' => 'integer',
        'is_international' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function hasWarranty(): bool
    {
        return $this->warranty_type !== self::TYPE_NONE && $this->warranty_period_months > 0;
    }

    public function getWarrantyPeriodLabelAttribute(): string
    {
        if (!$this->hasWarranty()) {
            return 'No Warranty';
        }

        $months = $this->warranty_period_months;
        
        if ($months >= 12 && $months % 12 === 0) {
            $years = $months / 12;
            return $years . ' Year' . ($years > 1 ? 's' : '');
        }

        return $months . ' Month' . ($months > 1 ? 's' : '');
    }
}
