<?php

namespace App\Models\Marketplace;

use App\Models\Marketplace\Cart;
use App\Models\Marketplace\CartItem;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\Warehouse;
use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartReservation extends Model
{
    protected $fillable = [
        'cart_id',
        'cart_item_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'marketplace_id',
        'quantity_reserved',
        'reserved_at',
        'expires_at',
        'released_at',
        'status',
        'release_reason',
        'metadata',
    ];

    protected $casts = [
        'quantity_reserved' => 'integer',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_RELEASED = 'released';
    const STATUS_CONVERTED = 'converted';
    const STATUS_EXPIRED = 'expired';

    /**
     * Reservation duration in minutes (15 minutes as per requirement)
     */
    const RESERVATION_DURATION_MINUTES = 15;

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Scope to get only active reservations
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired reservations
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if reservation is still valid
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->expires_at->isFuture();
    }

    /**
     * Mark reservation as released (manual release or expiration)
     */
    public function release(string $reason = null): void
    {
        $this->update([
            'status' => $this->isValid() ? self::STATUS_RELEASED : self::STATUS_EXPIRED,
            'released_at' => now(),
            'release_reason' => $reason,
        ]);
    }

    /**
     * Mark reservation as converted (order placed successfully)
     */
    public function convert(): void
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'released_at' => now(),
        ]);
    }

    /**
     * Calculate remaining time in seconds
     */
    public function remainingSeconds(): int
    {
        if (!$this->isValid()) {
            return 0;
        }

        return max(0, $this->expires_at->diffInSeconds(now()));
    }

    /**
     * Extend reservation by specified minutes
     */
    public function extend(int $minutes = null): void
    {
        $minutes = $minutes ?? self::RESERVATION_DURATION_MINUTES;
        $newExpiry = now()->addMinutes($minutes);

        if ($newExpiry->isFuture()) {
            $this->update(['expires_at' => $newExpiry]);
        }
    }
}
