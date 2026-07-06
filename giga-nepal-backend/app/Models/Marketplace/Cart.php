<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'marketplace_id',
        'currency_code',
        'subtotal',
        'tax_total',
        'discount_total',
        'shipping_total',
        'grand_total',
        'item_count',
        'is_active',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'item_count' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Recalculate line subtotals/totals from unit prices stored on the items.
     * Prices are never taken from client input — they are set server-side
     * when the item is added (see CartController / AiCartService).
     */
    public function calculateTotal(): void
    {
        $this->loadMissing('items');

        $subtotalTotal = 0.0;
        $taxTotal = 0.0;
        $discountTotal = 0.0;
        $grandTotal = 0.0;
        $itemCount = 0;

        foreach ($this->items as $item) {
            $subtotal = (float) $item->unit_price * (int) $item->quantity;
            $tax = (float) $item->tax_amount;
            $discount = (float) $item->discount_amount;
            $lineTotal = $subtotal + $tax - $discount;

            $item->forceFill([
                'subtotal' => $subtotal,
                'total' => $lineTotal,
            ])->save();

            $subtotalTotal += $subtotal;
            $taxTotal += $tax;
            $discountTotal += $discount;
            $grandTotal += $lineTotal;
            $itemCount += (int) $item->quantity;
        }

        $this->forceFill([
            'subtotal' => $subtotalTotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal + (float) $this->shipping_total,
            'item_count' => $itemCount,
        ])->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
