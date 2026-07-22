<?php

namespace App\Models\Freight;

use App\Models\Marketplace\Product;
use App\Models\Warehouse\Warehouse;
use App\Models\Purchasing\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandedCostAllocation extends Model
{
    protected $fillable = [
        'freight_shipment_id',
        'purchase_order_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'allocated_cost',
        'allocation_method',
        'original_cost',
        'total_landed_cost',
        'cost_per_unit',
        'currency',
        'posted_to_inventory',
        'posted_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'allocated_cost' => 'decimal:4',
        'original_cost' => 'decimal:4',
        'total_landed_cost' => 'decimal:4',
        'cost_per_unit' => 'decimal:4',
        'posted_to_inventory' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function freightShipment(): BelongsTo
    {
        return $this->belongsTo(FreightShipment::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopePosted($query)
    {
        return $query->where('posted_to_inventory', true);
    }

    public function scopeUnposted($query)
    {
        return $query->where('posted_to_inventory', false);
    }
}
