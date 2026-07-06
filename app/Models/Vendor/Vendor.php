<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends BaseModel
{
    protected $table = 'vendors';

    protected $fillable = [
        'user_id', 'name', 'slug', 'email', 'phone',
        'is_active', 'is_verified', 'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): HasMany
    {
        return $this->hasMany(VendorProfile::class);
    }

    public function marketplaceApprovals(): HasMany
    {
        return $this->hasMany(VendorMarketplaceApproval::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(VendorWarehouse::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(VendorStaff::class);
    }

    public function payoutMethods(): HasMany
    {
        return $this->hasMany(VendorPayoutMethod::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(VendorRating::class);
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Product\Product::class);
    }

    public function inventory()
    {
        return $this->hasMany(\App\Models\Inventory\VendorInventory::class);
    }
}
