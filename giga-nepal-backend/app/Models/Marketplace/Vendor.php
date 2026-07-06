<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'email',
        'phone',
        'website',
        'description',
        'logo_path',
        'banner_path',
        'country_id',
        'tax_number',
        'registration_number',
        'status',
        'type',
        'is_verified',
        'social_links',
        'metadata',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'social_links' => 'array',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
