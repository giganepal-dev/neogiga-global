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
        'marketplace_id',
        'base_price',
        'sale_price',
        'cost_price',
        'currency_code',
        'price_valid_from',
        'price_valid_until',
        'offer_start_date',
        'offer_end_date',
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
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'is_published',
        'published_at',
        'paused_at',
        'pause_reason',
        'is_featured',
        'is_buybox_winner',
        'seller_sku',
        'seller_notes',
        'conditions',
        'date_code',
        'condition_grade',
        'packaging_type',
        'lot_number',
        'country_of_origin',
        'warranty_type',
        'warranty_period',
        'warranty_terms',
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
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'paused_at' => 'datetime',
        'approved_at' => 'datetime',
        'price_valid_from' => 'date',
        'price_valid_until' => 'date',
        'offer_start_date' => 'date',
        'offer_end_date' => 'date',
        'last_synced_at' => 'datetime',
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

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SellerInventoryMovement::class, 'seller_offer_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(SellerShipment::class, 'seller_offer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeWithStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeForMarketplace($query, int $marketplaceId)
    {
        return $query->where('marketplace_id', $marketplaceId);
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

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function canBeSold(): bool
    {
        return $this->is_approved 
            && $this->is_published 
            && $this->status === 'active'
            && ($this->stock_quantity > 0 || $this->allow_backorder);
    }

    public function approve(int $approverId, ?string $notes = null): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function publish(): void
    {
        if (!$this->is_approved()) {
            throw new \RuntimeException('Cannot publish unapproved offer');
        }

        $this->update([
            'is_published' => true,
            'published_at' => now(),
            'paused_at' => null,
            'pause_reason' => null,
        ]);
    }

    public function pause(string $reason): void
    {
        $this->update([
            'is_published' => false,
            'paused_at' => now(),
            'pause_reason' => $reason,
        ]);
    }

    public function resume(): void
    {
        if (!$this->is_approved()) {
            throw new \RuntimeException('Cannot resume unapproved offer');
        }

        $this->update([
            'is_published' => true,
            'paused_at' => null,
            'pause_reason' => null,
        ]);
    }
}
