<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Country extends Model
{
    protected $primaryKey = 'iso_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'iso_code',
        'name',
        'native_name',
        'phone_code',
        'region',
        'subregion',
        'languages',
        'is_active',
        'requires_vat',
        'vat_label',
        'default_vat_rate',
    ];

    protected $casts = [
        'languages' => 'array',
        'is_active' => 'boolean',
        'requires_vat' => 'boolean',
        'default_vat_rate' => 'decimal:2',
    ];

    public function marketplaces(): HasMany
    {
        return $this->hasMany(Marketplace::class, 'country_code', 'iso_code');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'country_code', 'iso_code');
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRule::class, 'country_code', 'iso_code');
    }

    public function shippingRules(): HasMany
    {
        return $this->hasMany(ShippingRule::class, 'destination_country', 'iso_code');
    }

    public function productLocalizations(): HasMany
    {
        return $this->hasMany(ProductLocalization::class, 'country_code', 'iso_code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
