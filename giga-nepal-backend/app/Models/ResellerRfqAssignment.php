<?php

namespace App\Models;

use App\Models\Erp\RfqRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerRfqAssignment extends Model
{
    protected $fillable = [
        'rfq_id', 'reseller_id', 'status', 'invited_at', 'deadline_at', 'admin_notes',
    ];

    protected $casts = ['invited_at' => 'datetime', 'deadline_at' => 'datetime'];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(ResellerRfqBid::class, 'assignment_id');
    }
}
