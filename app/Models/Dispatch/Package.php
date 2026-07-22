<?php

namespace App\Models\Dispatch;

use App\Models\Freight\Carrier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'dispatch_batch_id',
        'order_id',
        'package_number',
        'length',
        'width',
        'height',
        'weight',
        'package_type',
        'tracking_number',
        'carrier_id',
        'contents',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:3',
        'contents' => 'array',
    ];

    public function dispatchBatch(): BelongsTo
    {
        return $this->belongsTo(DispatchBatch::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function getVolumetricWeightAttribute(): ?float
    {
        if (!$this->length || !$this->width || !$this->height) {
            return null;
        }

        // Standard volumetric weight calculation (L x W x H / 5000 for cm/kg)
        return ($this->length * $this->width * $this->height) / 5000;
    }

    public function getDimensionalVolumeAttribute(): ?float
    {
        if (!$this->length || !$this->width || !$this->height) {
            return null;
        }

        return $this->length * $this->width * $this->height;
    }
}
