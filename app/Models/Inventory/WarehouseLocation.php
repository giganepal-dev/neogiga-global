<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseLocation extends Model
{
    protected $fillable = [
        'warehouse_id',
        'zone',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'capacity',
        'current_volume',
    ];

    protected $casts = [
        'capacity' => 'decimal:3',
        'current_volume' => 'decimal:3',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
