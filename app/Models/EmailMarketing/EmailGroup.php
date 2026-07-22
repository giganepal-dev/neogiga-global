<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class EmailGroup extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'group_type',
        'country_code',
        'region_id',
        'marketplace_id',
        'is_active',
        'is_primary',
        'default_language',
        'default_currency',
        'sender_identity_id',
        'email_provider',
        'physical_address',
        'unsubscribe_footer',
        'daily_send_limit',
        'hourly_send_limit',
        'per_second_rate',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'settings' => 'array',
        'daily_send_limit' => 'integer',
        'hourly_send_limit' => 'integer',
        'per_second_rate' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailGroup $group) {
            if (empty($group->uuid)) {
                $group->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Region::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class);
    }

    public function senderIdentity(): BelongsTo
    {
        return $this->belongsTo(EmailSenderIdentity::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(EmailSubscriber::class, 'email_group_subscriber')
            ->withPivot('assignment_source', 'is_primary', 'assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function primarySubscribers(): BelongsToMany
    {
        return $this->belongsToMany(EmailSubscriber::class, 'email_group_subscriber')
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(EmailCampaign::class, 'email_campaign_groups')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function providerConfig(): HasMany
    {
        return $this->hasMany(EmailProviderConfig::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(EmailImport::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('group_type', $type);
    }

    public function isCountryGroup(): bool
    {
        return $this->group_type === 'country';
    }

    public function getSubscriberCountAttribute(): int
    {
        return $this->subscribers()->count();
    }

    public function assignSubscriber(EmailSubscriber $subscriber, string $source = 'manual', bool $isPrimary = false, ?int $assignedBy = null): void
    {
        $this->subscribers()->syncWithoutUpdating([$subscriber->id], [
            'assignment_source' => $source,
            'is_primary' => $isPrimary,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
        ]);
    }

    public function removeSubscriber(EmailSubscriber $subscriber): void
    {
        $this->subscribers()->detach($subscriber->id);
    }
}
