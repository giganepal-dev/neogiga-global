<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;

class DistributorApplication extends Model
{
    protected $fillable = [
        'business_name', 'contact_person', 'email', 'phone', 'whatsapp', 'country_id', 'region_id', 'city_id',
        'distributor_type', 'territory_interest', 'current_business_categories', 'existing_dealer_network',
        'warehouse_available', 'monthly_capacity', 'message', 'status', 'reviewed_by', 'reviewed_at',
        'admin_notes', 'source', 'operating_scope', 'full_name', 'company_name', 'target_marketplace_ids', 'annual_turnover_range',
    ];

    protected $casts = [
        'current_business_categories' => 'array',
        'target_marketplace_ids' => 'array',
        'existing_dealer_network' => 'boolean',
        'warehouse_available' => 'boolean',
        'reviewed_at' => 'datetime',
    ];
}
