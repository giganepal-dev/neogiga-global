<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailSubscriber extends Model
{
    protected $table = 'email_subscribers';

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
        'marketplace_id',
        'country_id',
        'region_id',
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
        'metadata',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_email_sent_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'engagement_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    public const STATUSES = [
        'subscribed',
        'pending',
        'unsubscribed',
        'bounced',
        'complained',
        'suppressed',
        'invalid',
        'duplicate',
        'archived',
    ];

    public const TYPES = [
        'personal_customer',
        'institutional_customer',
        'reseller',
        'distributor',
        'manufacturer',
        'supplier',
        'educational_institution',
        'government_buyer',
        'industry',
        'engineer',
        'maker',
        'newsletter_subscriber',
        'lead',
        'other',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $subscriber): void {
            if (empty($subscriber->uuid)) {
                $subscriber->uuid = (string) Str::uuid();
            }

            // Normalize email
            if (! empty($subscriber->email)) {
                $subscriber->email = mb_strtolower(trim($subscriber->email));
                $subscriber->normalized_email = $subscriber->email;
            }

            // Generate full name if not provided
            if (empty($subscriber->full_name) && ! empty($subscriber->first_name) && ! empty($subscriber->last_name)) {
                $subscriber->full_name = trim("{$subscriber->first_name} {$subscriber->last_name}");
            }
        });

        static::updating(function (self $subscriber): void {
            if ($subscriber->isDirty('email')) {
                $subscriber->email = mb_strtolower(trim($subscriber->email));
                $subscriber->normalized_email = $subscriber->email;
            }
        });
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Country::class, 'country_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(EmailGroup::class, 'email_group_subscriber')
            ->withPivot(['assignment_source', 'is_primary', 'assigned_by', 'assigned_at', 'assignment_context'])
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(EmailTag::class, 'email_subscriber_tags')
            ->withPivot(['source', 'added_at'])
            ->withTimestamps();
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(EmailPreference::class);
    }

    public function consentLogs(): HasMany
    {
        return $this->hasMany(EmailConsentLog::class);
    }

    public function primaryGroup(): BelongsToMany
    {
        return $this->groups()->wherePivot('is_primary', true);
    }

    public function isSubscribed(): bool
    {
        return $this->status === 'subscribed' && ! $this->unsubscribed_at;
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed' || $this->unsubscribed_at !== null;
    }

    public function isSuppressed(): bool
    {
        return in_array($this->status, ['bounced', 'complained', 'suppressed'], true);
    }

    public function canReceiveMarketing(): bool
    {
        return $this->isSubscribed() && ! $this->isSuppressed();
    }

    public function incrementStat(string $field, int $amount = 1): void
    {
        $allowedFields = ['total_sent', 'total_delivered', 'total_opened', 'total_clicked', 'total_bounced', 'total_complaints'];
        
        if (in_array($field, $allowedFields, true)) {
            $this->increment($field, $amount);
        }
    }

    public function updateEngagementScore(): void
    {
        $score = 0;
        
        // Opens contribute positively
        if ($this->total_opened > 0) {
            $openRate = min(1.0, $this->total_opened / max(1, $this->total_sent));
            $score += $openRate * 40;
        }
        
        // Clicks contribute more positively
        if ($this->total_clicked > 0) {
            $clickRate = min(1.0, $this->total_clicked / max(1, $this->total_sent));
            $score += $clickRate * 40;
        }
        
        // Recent activity bonus
        if ($this->last_clicked_at && $this->last_clicked_at->diffInDays(now()) <= 30) {
            $score += 10;
        } elseif ($this->last_opened_at && $this->last_opened_at->diffInDays(now()) <= 30) {
            $score += 5;
        }
        
        // Negative for bounces and complaints
        if ($this->total_bounced > 0) {
            $score -= min(20, $this->total_bounced * 5);
        }
        if ($this->total_complaints > 0) {
            $score -= min(30, $this->total_complaints * 10);
        }
        
        $this->engagement_score = max(0, min(100, $score));
        $this->save();
    }
}
