<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_token_id',
        'notification_type',
        'title',
        'body',
        'data_payload',
        'marketplace_id',
        'status', // queued, processing, sent, delivered, failed, invalid_token, expired
        'provider_response',
        'error_message',
        'attempt_count',
        'sent_at',
        'delivered_at',
        'read_at',
        'clicked_at',
        'metadata',
        'idempotency_key',
    ];

    protected $casts = [
        'data_payload' => 'array',
        'provider_response' => 'array',
        'metadata' => 'array',
        'attempt_count' => 'integer',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deviceToken(): BelongsTo
    {
        return $this->belongsTo(FirebaseDeviceToken::class, 'device_token_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace\Marketplace::class);
    }
}
