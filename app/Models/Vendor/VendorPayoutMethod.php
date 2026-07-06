<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayoutMethod extends BaseModel
{
    protected $table = 'vendor_payout_methods';

    protected $fillable = [
        'vendor_id', 'method_type', 'account_holder_name',
        'account_number', 'bank_name', 'bank_code', 'branch_name',
        'routing_number', 'swift_code', 'iban', 'paypal_email',
        'is_default', 'is_verified', 'verified_at', 'metadata'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
