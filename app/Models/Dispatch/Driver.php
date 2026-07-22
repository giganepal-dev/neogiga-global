<?php

namespace App\Models\Dispatch;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_id',
        'user_id',
        'name',
        'license_number',
        'license_expiry',
        'phone',
        'email',
        'vehicle_type',
        'vehicle_number',
        'status',
        'cod_limit',
        'notes',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'cod_limit' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function marketplace()
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proofOfDeliveries(): HasMany
    {
        return $this->hasMany(\App\Models\Freight\ProofOfDelivery::class);
    }

    public function codCollections(): HasMany
    {
        return $this->hasMany(\App\Models\Freight\CodCollection::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOnRoute($query)
    {
        return $query->where('status', 'on_route');
    }

    public function scopeOffDuty($query)
    {
        return $query->where('status', 'off_duty');
    }
}
