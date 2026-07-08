<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorOrderItem extends Model
{
    protected $fillable = [
        'vendor_order_id',
        'order_item_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'line_total',
        'commission_amount',
        'vendor_net_amount',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }
}
