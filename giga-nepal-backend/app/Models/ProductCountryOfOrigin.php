<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCountryOfOrigin extends Model
{
    protected $fillable = [
        'product_id',
        'country_id',
        'origin_type',
        'manufacturer_details',
        'manufacturer_name',
        'manufacturer_address',
        'importer_name',
        'importer_address',
        'hs_code',
    ];

    const ORIGIN_MANUFACTURED = 'manufactured';
    const ORIGIN_ASSEMBLED = 'assembled';
    const ORIGIN_DESIGNED = 'designed';

    public static function getOriginTypes(): array
    {
        return [
            self::ORIGIN_MANUFACTURED,
            self::ORIGIN_ASSEMBLED,
            self::ORIGIN_DESIGNED,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('origin_type', $type);
    }
}
