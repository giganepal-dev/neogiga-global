<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_request_id',
        'user_id',
        'role',
        'is_primary',
        'notes',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function rfqRequest(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
