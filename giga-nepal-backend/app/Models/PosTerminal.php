<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTerminal extends Model
{
    protected $fillable = [
        'terminal_name',
        'terminal_code',
        'marketplace_id',
        'vendor_id',
        'warehouse_id',
        'status',
        'location',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Vendor::class, 'vendor_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Warehouse::class, 'warehouse_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }
}
