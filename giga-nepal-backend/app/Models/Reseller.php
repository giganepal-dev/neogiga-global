<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'trading_name', 'registration_number',
        'tax_number', 'country_id', 'region', 'business_address',
        'contact_person', 'email', 'phone', 'website', 'status', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
