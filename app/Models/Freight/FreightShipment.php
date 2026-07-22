<?php

namespace App\Models\Freight;

use App\Models\Marketplace\Marketplace;
use App\Models\Warehouse\Warehouse;
use App\Models\Purchasing\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreightShipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_id',
        'warehouse_id',
        'shipment_type',
        'shipment_number',
        'awb_number',
        'bl_number',
        'container_number',
        'tracking_number',
        'supplier_id',
        'carrier_id',
        'freight_forwarder_id',
        'origin_country',
        'origin_port',
        'destination_country',
        'destination_port',
        'incoterm',
        'gross_weight',
        'volumetric_weight',
        'chargeable_weight',
        'volume_cbm',
        'package_count',
        'freight_cost',
        'insurance_cost',
        'customs_duty',
        'other_charges',
        'currency',
        'expected_departure_date',
        'expected_arrival_date',
        'actual_departure_date',
        'actual_arrival_date',
        'status',
        'documents',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gross_weight' => 'decimal:3',
        'volumetric_weight' => 'decimal:3',
        'chargeable_weight' => 'decimal:3',
        'volume_cbm' => 'decimal:3',
        'freight_cost' => 'decimal:4',
        'insurance_cost' => 'decimal:4',
        'customs_duty' => 'decimal:4',
        'other_charges' => 'decimal:4',
        'expected_departure_date' => 'date',
        'expected_arrival_date' => 'date',
        'actual_departure_date' => 'date',
        'actual_arrival_date' => 'date',
        'documents' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function freightForwarder(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'freight_forwarder_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(FreightExpense::class);
    }

    public function landedCostAllocations(): HasMany
    {
        return $this->hasMany(LandedCostAllocation::class);
    }

    public function getTotalCostAttribute(): float
    {
        return $this->freight_cost + 
               $this->insurance_cost + 
               $this->customs_duty + 
               $this->other_charges;
    }

    public function scopeInbound($query)
    {
        return $query->where('shipment_type', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('shipment_type', 'outbound');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeArrived($query)
    {
        return $query->where('status', 'arrived');
    }
}
