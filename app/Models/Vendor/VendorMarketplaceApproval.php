<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorMarketplaceApproval extends BaseModel
{
    protected $table = 'vendor_marketplace_approvals';

    protected $fillable = [
        'vendor_id', 'marketplace_id', 'status',
        'submitted_at', 'reviewed_at', 'reviewed_by',
        'approval_notes', 'rejection_reason', 'metadata'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUSPENDED = 'suspended';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function marketplace()
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }
}
