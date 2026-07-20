<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerGlobalInventory extends Model
{
    protected $table = 'manufacturer_global_inventory';

    protected $fillable = [
        'manufacturer_id',
        'product_id',
        'sku',
        'quantity_on_hand',
        'quantity_reserved',
        'unit_cost',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }
}
