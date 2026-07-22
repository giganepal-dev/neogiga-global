<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class EmailCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'status',
        'failure_reason',
        'queued_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'open_count',
        'click_count',
        'provider_message_id',
        'metadata',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'metadata' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class);
    }

    public function subscriber()
    {
        return $this->belongsTo(EmailSubscriber::class);
    }

    public function deliveryEvents(): HasMany
    {
        return $this->hasMany(EmailDeliveryEvent::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function markAsQueued(): void
    {
        $this->update(['status' => 'queued', 'queued_at' => now()]);
    }

    public function markAsSent(string $providerMessageId): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $providerMessageId,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsOpened(): void
    {
        $this->increment('open_count');
        if ($this->open_count === 1) {
            $this->update(['opened_at' => now()]);
        }
    }

    public function markAsClicked(): void
    {
        $this->increment('click_count');
        if ($this->click_count === 1) {
            $this->update(['clicked_at' => now()]);
        }
    }

    public function markAsBounced(string $reason = null): void
    {
        $this->update([
            'status' => 'bounced',
            'failure_reason' => $reason,
        ]);
    }

    public function markAsComplained(): void
    {
        $this->update(['status' => 'complained']);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    public function markAsSkipped(string $reason): void
    {
        $this->update([
            'status' => 'skipped',
            'failure_reason' => $reason,
        ]);
    }
}
