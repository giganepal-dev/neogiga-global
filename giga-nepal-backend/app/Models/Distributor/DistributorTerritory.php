<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;

class DistributorTerritory extends Model
{
    protected $fillable = ['distributor_id', 'country_id', 'region_id', 'city_id', 'territory_name', 'exclusive', 'can_manage_downlines'];

    protected $casts = ['exclusive' => 'boolean', 'can_manage_downlines' => 'boolean'];
}
