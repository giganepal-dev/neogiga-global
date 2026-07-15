<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Seller Offer - A seller's offer for a canonical product
 */
class SellerOffer extends Model
{
    use SoftDeletes;

    protected $table = 'seller_offers';

    protected $fillable = [
        'canonical_product_id',
        'variation_id',
        'seller_id',
        'warehouse_id',
        'base_price',
        'sale_price',
        'cost_price',
        'currency_code',
        'price_valid_from',
        'price_valid_until',
        'quantity_breaks',
        'moq',
        'order_multiple',
        'max_order_qty',
        'stock_quantity',
        'reserved_quantity',
        'incoming_quantity',
        'allow_backorder',
        'backorder_limit',
        'allow_preorder',
        'preorder_available_date',
        'lead_time_days',
        'fulfillment_type',
        'shipping_restrictions',
        'status',
        'is_featured',
        'is_buybox_winner',
        'seller_sku',
        'seller_notes',
        'conditions',
        'sales_count',
        'rating_average',
        'rating_count',
        'last_synced_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'base_price' => 'decimal:4',
        'sale_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'quantity_breaks' => 'array',
        'moq' => 'integer',
        'order_multiple' => 'integer',
        'max_order_qty' => 'integer',
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'incoming_quantity' => 'integer',
        'allow_backorder' => 'boolean',
        'backorder_limit' => 'integer',
        'allow_preorder' => 'boolean',
        'preorder_available_date' => 'date',
        'lead_time_days' => 'integer',
        'is_featured' => 'boolean',
        'is_buybox_winner' => 'boolean',
        'conditions' => 'array',
        'sales_count' => 'integer',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'last_synced_at' => 'datetime',
        'price_valid_from' => 'date',
        'price_valid_until' => 'date',
    ];

    public function canonicalProduct(): BelongsTo
    {
        return $this->belongsTo(CanonicalProduct::class, 'canonical_product_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variation_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->base_price;
    }

    public function isBuyBoxWinner(): bool
    {
        return $this->is_buybox_winner && $this->status === 'active';
    }
}
