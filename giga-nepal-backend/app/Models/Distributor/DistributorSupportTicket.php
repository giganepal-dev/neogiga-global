<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributorSupportTicket extends Model
{
    protected $fillable = [
        'distributor_id',
        'user_id',
        'ticket_number',
        'subject',
        'body',
        'status',
        'priority',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }
}
