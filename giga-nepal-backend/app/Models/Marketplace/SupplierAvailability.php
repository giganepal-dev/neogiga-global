<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAvailability extends Model
{
    protected $fillable = [
        'product_id',
        'catalog_source_id',
        'catalog_distributor_offer_id',
        'warehouse_id',
        'marketplace_id',
        'source_part_id',
        'supplier_name',
        'observed_offer_stock',
        'desired_quantity',
        'total_available_quantity',
        'allocated_quantity',
        'allocation_percent',
        'stock_type',
        'availability_status',
        'quote_only',
        'is_reservable',
        'is_fulfillable',
        'is_active',
        'allocation_policy',
        'source_observed_at',
        'source_name',
        'source_url',
        'source_file',
        'source_page_url',
        'downloaded_at',
        'imported_at',
        'data_year',
        'license_note',
        'confidence_level',
        'managed_by',
        'is_manual_override',
        'is_locked',
        'original_raw_value',
        'normalized_value',
    ];

    protected $casts = [
        'observed_offer_stock' => 'integer',
        'desired_quantity' => 'integer',
        'total_available_quantity' => 'integer',
        'allocated_quantity' => 'integer',
        'allocation_percent' => 'decimal:2',
        'quote_only' => 'boolean',
        'is_reservable' => 'boolean',
        'is_fulfillable' => 'boolean',
        'is_active' => 'boolean',
        'is_manual_override' => 'boolean',
        'is_locked' => 'boolean',
        'source_observed_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'imported_at' => 'datetime',
        'original_raw_value' => 'array',
        'normalized_value' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }
}
