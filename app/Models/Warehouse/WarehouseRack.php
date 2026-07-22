<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseRack extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouse_racks';

    protected $fillable = [
        'aisle_id',
        'name',
        'code',
        'sequence',
        'levels',
        'max_weight_kg',
        'max_height_cm',
        'description',
        'is_active',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'levels' => 'integer',
        'max_weight_kg' => 'decimal:2',
        'max_height_cm' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function aisle(): BelongsTo
    {
        return $this->belongsTo(WarehouseAisle::class, 'aisle_id');
    }

    public function shelves(): HasMany
    {
        return $this->hasMany(WarehouseShelf::class, 'rack_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAisle($query, $aisleId)
    {
        return $query->where('aisle_id', $aisleId);
    }

    public function getFullLocationPathAttribute(): string
    {
        return "{$this->aisle->zone->warehouse->name} > {$this->aisle->zone->name} > {$this->aisle->name} > {$this->name}";
    }
}
