<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Marketplace extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'country_code',
        'subdomain',
        'name',
        'short_name',
        'currency_code',
        'timezone',
        'locale',
        'supported_locales',
        'is_active',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'supported_locales' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'iso_code');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(MarketplaceSetting::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasManyThrough(Warehouse::class, Country::class, 'iso_code', 'country_code', 'country_code', 'iso_code');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function paymentGateways(): HasMany
    {
        return $this->hasMany(PaymentGateway::class);
    }

    public function localizedPages(): HasMany
    {
        return $this->hasMany(LocalizedPage::class);
    }

    public function localizedSeo(): HasMany
    {
        return $this->hasMany(LocalizedSeo::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductMarketplacePrice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullDomainAttribute(): string
    {
        return "{$this->subdomain}.neogiga.com";
    }

    public function getBaseUrlAttribute(): string
    {
        return "https://{$this->full_domain}";
    }
}
