<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseShipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'shipment_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'type',
        'status',
        'total_items',
        'total_weight',
        'carrier',
        'tracking_number',
        'expected_departure_date',
        'expected_arrival_date',
        'actual_departure_at',
        'actual_arrival_at',
        'customs_documents',
        'metadata',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'total_weight' => 'decimal:2',
        'expected_departure_date' => 'date',
        'expected_arrival_date' => 'date',
        'actual_departure_at' => 'datetime',
        'actual_arrival_at' => 'datetime',
        'customs_documents' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (!$shipment->shipment_number) {
                $prefix = strtoupper(substr($shipment->type, 0, 3));
                $shipment->shipment_number = $prefix . '-' . date('Ymd') . '-' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Get the source warehouse
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the destination warehouse
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get shipment items
     */
    public function items(): HasMany
    {
        return $this->hasMany(WarehouseShipmentItem::class);
    }

    /**
     * Scope for pending shipments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in-transit shipments
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope for cross-border shipments
     */
    public function scopeCrossBorder($query)
    {
        return $query->whereHas('fromWarehouse', function ($q) {
            $q->where('allows_cross_border', true);
        })->whereHas('toWarehouse', function ($q) {
            $q->where('allows_cross_border', true);
        });
    }

    /**
     * Check if shipment is cross-border
     */
    public function isCrossBorder(): bool
    {
        return $this->fromWarehouse && 
               $this->toWarehouse && 
               $this->fromWarehouse->country !== $this->toWarehouse->country;
    }

    /**
     * Mark shipment as departed
     */
    public function markAsDeparted(): void
    {
        $this->update([
            'status' => 'in_transit',
            'actual_departure_at' => now(),
        ]);
    }

    /**
     * Mark shipment as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'actual_arrival_at' => now(),
        ]);
    }

    /**
     * Calculate total items count
     */
    public function calculateTotalItems(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Update shipment status based on items
     */
    public function updateStatus(): void
    {
        $totalItems = $this->calculateTotalItems();
        $this->update(['total_items' => $totalItems]);
    }
}
