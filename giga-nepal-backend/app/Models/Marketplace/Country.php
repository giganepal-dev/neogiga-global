<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_code_2',
        'iso_code_3',
        'phone_code',
        'currency_id',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function currency(): HasMany
    {
        return $this->hasMany(Currency::class, 'id', 'currency_id');
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function marketplaces(): HasMany
    {
        return $this->hasMany(Marketplace::class);
    }
}
