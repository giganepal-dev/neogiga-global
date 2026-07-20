<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerSupportTicket extends Model
{
    protected $fillable = [
        'reseller_id', 'user_id', 'ticket_number', 'subject', 'body', 'status', 'priority',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
