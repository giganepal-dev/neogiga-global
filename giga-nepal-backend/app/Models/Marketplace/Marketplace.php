<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Marketplace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'country_id',
        'currency_id',
        'timezone',
        'locale',
        'is_active',
        'is_default',
        'allow_vendor_registration',
        'require_vendor_approval',
        'tax_rate',
        'supported_languages',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'allow_vendor_registration' => 'boolean',
        'require_vendor_approval' => 'boolean',
        'tax_rate' => 'decimal:5',
        'supported_languages' => 'array',
        'settings' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(MarketplaceDomain::class);
    }

    public function settingsRecord(): HasOne
    {
        return $this->hasOne(MarketplaceSetting::class);
    }

    public function taxZones(): HasMany
    {
        return $this->hasMany(TaxZone::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function vendorApprovals(): HasMany
    {
        return $this->hasManyThrough(
            \App\Models\Marketplace\VendorMarketplaceApproval::class,
            \App\Models\Marketplace\Vendor::class,
            'id',
            'marketplace_id',
            'vendor_id',
            'id'
        );
    }
}
