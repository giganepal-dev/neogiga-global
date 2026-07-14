<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_provider_id',
        'email_template_id',
        'recipient_email',
        'subject',
        'message_id',
        'status', // queued, processing, sent, accepted, delivered, deferred, bounced, complained, rejected, failed, suppressed, cancelled
        'provider_response',
        'error_message',
        'attempt_count',
        'last_attempt_at',
        'sent_at',
        'delivered_at',
        'bounced_at',
        'complained_at',
        'metadata',
        'idempotency_key',
        'marketplace_id',
        'user_id',
        'order_id',
        'event_type',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'metadata' => 'array',
        'attempt_count' => 'integer',
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(EmailProvider::class, 'email_provider_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace\Marketplace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
