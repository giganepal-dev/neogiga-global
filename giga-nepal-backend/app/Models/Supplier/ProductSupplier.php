<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Marketplace\Product;

class ProductSupplier extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'supplier_product_id',
        'supplier_sku',
        'mpn',
        'upc_ean',
        'cost_price',
        'currency',
        'lead_time_days',
        'min_order_quantity',
        'is_primary',
        'is_active',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'date',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByMpn($query, $mpn)
    {
        return $query->where('mpn', $mpn);
    }
}
