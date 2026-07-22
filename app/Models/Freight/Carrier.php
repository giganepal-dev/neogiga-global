<?php

namespace App\Models\Freight;

use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Carrier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_id',
        'name',
        'code',
        'type',
        'contact_name',
        'email',
        'phone',
        'website',
        'tracking_url_template',
        'is_active',
        'service_areas',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'service_areas' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function freightShipments(): HasMany
    {
        return $this->hasMany(FreightShipment::class, 'carrier_id');
    }

    public function freightForwarderShipments(): HasMany
    {
        return $this->hasMany(FreightShipment::class, 'freight_forwarder_id');
    }

    public function dispatchBatches(): HasMany
    {
        return $this->hasMany(DispatchBatch::class, 'carrier_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'carrier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getTrackingUrlAttribute(?string $trackingNumber = null): ?string
    {
        if (!$this->tracking_url_template || !$trackingNumber) {
            return null;
        }

        return str_replace('{TRACKING_NUMBER}', $trackingNumber, $this->tracking_url_template);
    }
}
