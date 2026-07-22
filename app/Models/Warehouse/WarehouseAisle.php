<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseAisle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouse_aisles';

    protected $fillable = [
        'zone_id',
        'name',
        'code',
        'sequence',
        'description',
        'is_active',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    public function racks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class, 'aisle_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function getFullLocationPathAttribute(): string
    {
        return "{$this->zone->warehouse->name} > {$this->zone->name} > {$this->name}";
    }
}
