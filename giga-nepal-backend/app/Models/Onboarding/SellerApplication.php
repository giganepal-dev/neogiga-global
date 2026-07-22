<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;

class SellerApplication extends Model
{
    protected $fillable = [
        'business_name', 'contact_person', 'email', 'phone', 'whatsapp', 'country_id', 'region_id', 'city_id',
        'business_type', 'seller_type', 'product_categories', 'brands_carried', 'has_existing_inventory',
        'has_physical_store', 'monthly_order_capacity', 'website', 'message', 'status', 'reviewed_by',
        'reviewed_at', 'admin_notes', 'source', 'operating_scope', 'country', 'target_marketplace_ids',
    ];

    protected $casts = [
        'product_categories' => 'array',
        'brands_carried' => 'array',
        'target_marketplace_ids' => 'array',
        'has_existing_inventory' => 'boolean',
        'has_physical_store' => 'boolean',
        'reviewed_at' => 'datetime',
    ];
}
