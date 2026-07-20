<?php

namespace App\Models;

use App\Models\Erp\RfqRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerRfqBid extends Model
{
    protected $fillable = [
        'rfq_id', 'assignment_id', 'reseller_id', 'status', 'cover_note',
        'currency', 'lead_time_days', 'valid_until', 'submitted_at',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ResellerRfqAssignment::class, 'assignment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResellerRfqBidItem::class, 'bid_id');
    }
}
