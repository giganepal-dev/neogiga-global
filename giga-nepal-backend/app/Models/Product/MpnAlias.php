<?php

namespace App\Models\Product;

use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpnAlias extends Model
{
    protected $fillable = [
        'product_id',
        'alias_mpn',
        'normalized_alias',
        'alias_type',
        'source',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Normalize an MPN for storage.
     */
    public static function normalizeMpn(string $mpn): string
    {
        return strtoupper(preg_replace('/\s+/', '', $mpn));
    }

    /**
     * Scope to active aliases.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by alias type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alias_type', $type);
    }
}
