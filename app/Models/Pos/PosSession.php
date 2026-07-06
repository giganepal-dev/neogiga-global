<?php

namespace App\Models\Pos;

use App\Models\BaseModel;
use App\Models\Marketplace\Marketplace;
use App\Models\Vendor\Vendor;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSession extends BaseModel
{
    protected $table = 'pos_sessions';

    protected $fillable = [
        'pos_terminal_id',
        'vendor_id',
        'warehouse_id',
        'marketplace_id',
        'opened_at',
        'closed_at',
        'starting_cash',
        'ending_cash',
        'status', // open, closed, suspended
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_cash' => 'decimal:2',
        'ending_cash' => 'decimal:2',
    ];

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function marketplace(): BelongsTo
    {
        $this->belongsTo(Marketplace::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'pos_session_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'pos_session_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
