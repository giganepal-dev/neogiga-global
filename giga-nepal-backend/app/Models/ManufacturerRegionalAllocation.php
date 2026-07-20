<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerRegionalAllocation extends Model
{
    protected $fillable = [
        'manufacturer_id',
        'marketplace_id',
        'warehouse_id',
        'product_id',
        'quantity_allocated',
        'status',
        'notes',
        'allocated_at',
        'metadata',
    ];

    protected $casts = [
        'allocated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }
}
