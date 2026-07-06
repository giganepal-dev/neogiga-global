<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorWarehouse extends BaseModel
{
    protected $table = 'vendor_warehouses';

    protected $fillable = [
        'vendor_id', 'warehouse_id', 'is_primary', 'metadata'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Inventory\Warehouse::class);
    }
}
