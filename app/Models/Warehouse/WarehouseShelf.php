<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseShelf extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouse_shelves';

    protected $fillable = [
        'rack_id',
        'name',
        'code',
        'level_number',
        'sequence',
        'max_weight_kg',
        'description',
        'is_active',
    ];

    protected $casts = [
        'level_number' => 'integer',
        'sequence' => 'integer',
        'max_weight_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rack(): BelongsTo
    {
        return $this->belongsTo(WarehouseRack::class, 'rack_id');
    }

    public function bins(): HasMany
    {
        return $this->hasMany(WarehouseBin::class, 'shelf_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRack($query, $rackId)
    {
        return $query->where('rack_id', $rackId);
    }

    public function getFullLocationPathAttribute(): string
    {
        return "{$this->rack->aisle->zone->warehouse->name} > {$this->rack->aisle->zone->name} > {$this->rack->aisle->name} > {$this->rack->name} > {$this->name}";
    }
}
