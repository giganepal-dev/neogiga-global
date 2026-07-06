<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProfile extends BaseModel
{
    protected $table = 'vendor_profiles';

    protected $fillable = [
        'vendor_id', 'marketplace_id', 'country_id',
        'business_name', 'business_type', 'registration_number',
        'tax_id', 'description', 'logo_path', 'banner_path',
        'website', 'social_links', 'address_line_1', 'address_line_2',
        'city', 'region', 'postal_code', 'phone', 'email',
        'is_complete', 'metadata'
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_complete' => 'boolean',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function marketplace()
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class);
    }

    public function country()
    {
        return $this->belongsTo(\App\Models\Marketplace\Country::class);
    }
}
