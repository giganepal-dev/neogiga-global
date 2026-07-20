<?php

namespace App\Models\Erp;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqBid extends Model
{
    protected $guarded = [];

    protected $casts = [
        'terms' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RfqAssignment::class, 'assignment_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqBidItem::class, 'bid_id');
    }
}
