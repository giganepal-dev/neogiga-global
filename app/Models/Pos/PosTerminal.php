<?php

namespace App\Models\Pos;

use App\Models\BaseModel;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTerminal extends BaseModel
{
    protected $table = 'pos_terminals';

    protected $fillable = [
        'name',
        'code',
        'warehouse_id',
        'is_active',
        'meta_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }
}
