<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class EmailSubscriber extends Model
{
    protected $fillable = [
        'uuid',
        'email',
        'normalized_email',
        'first_name',
        'last_name',
        'full_name',
        'company_name',
        'phone',
        'job_title',
        'subscriber_type',
        'customer_type',
        'source',
        'source_reference',
        'user_id',
        'region_id',
        'country_id',
        'country_code',
        'state_or_province',
        'city',
        'preferred_language',
        'preferred_currency',
        'timezone',
        'status',
        'email_verified_at',
        'subscribed_at',
        'unsubscribed_at',
        'last_email_sent_at',
        'last_opened_at',
        'last_clicked_at',
        'engagement_score',
        'total_sent',
        'total_delivered',
        'total_opened',
        'total_clicked',
        'total_bounced',
        'total_complaints',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_email_sent_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'engagement_score' => 'integer',
        'total_sent' => 'integer',
        'total_delivered' => 'integer',
        'total_opened' => 'integer',
        'total_clicked' => 'integer',
        'total_bounced' => 'integer',
        'total_complaints' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailSubscriber $subscriber) {
            if (empty($subscriber->uuid)) {
                $subscriber->uuid = Uuid::uuid4()->toString();
            }
            
            // Normalize email
            if (!empty($subscriber->email)) {
                $subscriber->email = trim(strtolower($subscriber->email));
                $subscriber->normalized_email = $subscriber->email;
            }

            // Generate full name if not provided
            if (empty($subscriber->full_name) && !empty($subscriber->first_name) && !empty($subscriber->last_name)) {
                $subscriber->full_name = trim("{$subscriber->first_name} {$subscriber->last_name}");
            }
        });

        static::updating(function (EmailSubscriber $subscriber) {
            if (!empty($subscriber->email)) {
                $subscriber->email = trim(strtolower($subscriber->email));
                $subscriber->normalized_email = $subscriber->email;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Region::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Country::class, 'country_id', 'iso_code');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(EmailGroup::class, 'email_group_subscriber')
            ->withPivot('assignment_source', 'is_primary', 'assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function primaryGroup(): BelongsToMany
    {
        return $this->belongsToMany(EmailGroup::class, 'email_group_subscriber')
            ->wherePivot('is_primary', true)
            ->withPivot('assignment_source', 'is_primary', 'assigned_at', 'assigned_by')
            ->limit(1);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(EmailTag::class, 'email_subscriber_tags')
            ->withPivot('source', 'assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function consents(): HasMany
    {
        return $this->hasMany(EmailConsent::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(EmailPreference::class);
    }

    public function suppressions(): HasMany
    {
        return $this->hasMany(EmailSuppression::class);
    }

    public function campaignRecipients(): HasMany
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

    public function regionalAssignmentsLog(): HasMany
    {
        return $this->hasMany(EmailRegionalAssignmentLog::class);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('status', 'subscribed');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['subscribed', 'pending']);
    }

    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('subscriber_type', $type);
    }

    public function scopeWithEngagement($query, int $minScore = 0)
    {
        return $query->where('engagement_score', '>=', $minScore);
    }

    public function isSubscribed(): bool
    {
        return $this->status === 'subscribed';
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed';
    }

    public function isSuppressed(): bool
    {
        return in_array($this->status, ['suppressed', 'bounced', 'complained']);
    }

    public function canReceiveMarketing(): bool
    {
        return $this->isSubscribed() && !$this->isSuppressed();
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    public function subscribe(): void
    {
        $this->update([
            'status' => 'subscribed',
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);
    }

    public function updateEngagement(string $event): void
    {
        $updates = [];

        switch ($event) {
            case 'opened':
                $updates['last_opened_at'] = now();
                $updates['engagement_score'] = min(100, $this->engagement_score + 5);
                break;
            case 'clicked':
                $updates['last_clicked_at'] = now();
                $updates['engagement_score'] = min(100, $this->engagement_score + 10);
                break;
            case 'bounced':
                $updates['total_bounced'] = $this->total_bounced + 1;
                $updates['engagement_score'] = max(0, $this->engagement_score - 20);
                break;
            case 'complained':
                $updates['total_complaints'] = $this->total_complaints + 1;
                $updates['engagement_score'] = max(0, $this->engagement_score - 50);
                break;
        }

        if (!empty($updates)) {
            $this->update($updates);
        }
    }

    public function getFullNameAttribute(?string $value): ?string
    {
        if ($value) {
            return $value;
        }

        if ($this->first_name && $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }

        return $this->first_name ?? $this->last_name ?? null;
    }
}
