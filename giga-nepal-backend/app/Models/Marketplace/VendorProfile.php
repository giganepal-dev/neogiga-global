<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'about',
        'business_type',
        'years_in_business',
        'employee_count',
        'annual_revenue',
        'certifications',
        'specialties',
        'shipping_methods',
        'payment_methods',
        'return_policy',
        'warranty_policy',
        'response_time_hours',
        'rating_average',
        'total_reviews',
        'total_sales',
        'metadata',
    ];

    protected $casts = [
        'years_in_business' => 'integer',
        'employee_count' => 'integer',
        'annual_revenue' => 'decimal:2',
        'certifications' => 'array',
        'specialties' => 'array',
        'shipping_methods' => 'array',
        'payment_methods' => 'array',
        'rating_average' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
