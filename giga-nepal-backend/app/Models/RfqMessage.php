<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_request_id',
        'user_id',
        'sender_type',
        'message',
        'is_internal',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
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
