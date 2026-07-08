<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorOrder extends Model
{
    protected $fillable = [
        'vendor_id',
        'order_id',
        'vendor_order_number',
        'status',
        'payment_status',
        'currency_code',
        'subtotal',
        'tax_total',
        'shipping_total',
        'commission_total',
        'vendor_net_total',
        'fulfilled_at',
        'metadata',
    ];

    protected $casts = [
        'fulfilled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorOrderItem::class);
    }
}
