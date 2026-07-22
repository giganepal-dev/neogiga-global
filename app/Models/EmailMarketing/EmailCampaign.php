<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Ramsey\Uuid\Uuid;

class EmailCampaign extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'subject',
        'preview_text',
        'sender_name',
        'sender_email',
        'reply_to_email',
        'template_id',
        'html_content',
        'text_content',
        'language',
        'provider',
        'status',
        'category',
        'scheduled_at',
        'timezone',
        'started_at',
        'completed_at',
        'track_opens',
        'track_clicks',
        'utm_params',
        'settings',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
        'utm_params' => 'array',
        'settings' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailCampaign $campaign) {
            if (empty($campaign->uuid)) {
                $campaign->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'approved_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(EmailGroup::class, 'email_campaign_groups')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(EmailSegment::class, 'email_campaign_segments')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function deliveryEvents(): HasMany
    {
        return $this->hasMany(EmailDeliveryEvent::class);
    }

    public function clickEvents(): HasMany
    {
        return $this->hasMany(EmailClickEvent::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'validating', 'scheduled', 'queued', 'sending', 'paused']);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function canSend(): bool
    {
        return in_array($this->status, ['draft', 'validating', 'scheduled']);
    }

    public function canPause(): bool
    {
        return $this->status === 'sending';
    }

    public function canResume(): bool
    {
        return $this->status === 'paused';
    }

    public function getRecipientCountAttribute(): int
    {
        return $this->recipients()->count();
    }

    public function getSentCountAttribute(): int
    {
        return $this->recipients()->where('status', 'sent')->count();
    }

    public function getDeliveredCountAttribute(): int
    {
        return $this->recipients()->where('status', 'delivered')->count();
    }

    public function getOpenedCountAttribute(): int
    {
        return $this->recipients()->where('open_count', '>', 0)->count();
    }

    public function getClickedCountAttribute(): int
    {
        return $this->recipients()->where('click_count', '>', 0)->count();
    }

    public function getBouncedCountAttribute(): int
    {
        return $this->recipients()->where('status', 'bounced')->count();
    }

    public function getUnsubscribedCountAttribute(): int
    {
        return $this->recipients()->where('status', 'unsubscribed')->count();
    }

    public function startSending(): void
    {
        $this->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'sending']);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function fail(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }
}
