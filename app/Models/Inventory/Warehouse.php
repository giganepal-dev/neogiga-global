<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Country;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends BaseModel
{
    protected $table = 'warehouses';

    protected $fillable = [
        'name', 'code', 'type', 'marketplace_id', 'country_id',
        'address_line_1', 'address_line_2', 'city', 'region', 'postal_code',
        'latitude', 'longitude', 'phone', 'email', 'manager_name',
        'is_active', 'is_default', 'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'warehouse_id');
    }
}
