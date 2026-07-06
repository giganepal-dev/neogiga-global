<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorStaff extends BaseModel
{
    protected $table = 'vendor_staff';

    protected $fillable = [
        'vendor_id', 'user_id', 'role', 'permissions',
        'is_active', 'invited_at', 'accepted_at', 'metadata'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
