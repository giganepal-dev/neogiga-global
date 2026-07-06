<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalInventoryVisibility extends BaseModel
{
    protected $table = 'regional_inventory_visibility';

    protected $fillable = [
        'stock_id', 'marketplace_id',
        'is_visible', 'hide_until', 'visibility_reason', 'metadata'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'hide_until' => 'datetime',
        'metadata' => 'array',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }
}
