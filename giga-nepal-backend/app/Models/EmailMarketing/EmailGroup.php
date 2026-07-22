<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailGroup extends Model
{
    protected $table = 'email_groups';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'group_type',
        'country_code',
        'marketplace_id',
        'default_language',
        'default_currency',
        'sender_profile_id',
        'provider',
        'physical_address',
        'unsubscribe_footer',
        'daily_limit',
        'hourly_limit',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $group): void {
            if (empty($group->uuid)) {
                $group->uuid = (string) Str::uuid();
            }

            if (empty($group->slug) && ! empty($group->name)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(EmailSubscriber::class, 'email_group_subscriber')
            ->withPivot(['assignment_source', 'is_primary', 'assigned_by', 'assigned_at', 'assignment_context'])
            ->withTimestamps();
    }

    public function countryGroups(): HasMany
    {
        return $this->hasMany(EmailCountryGroup::class, 'primary_group_id');
    }

    public function primarySubscribers(): BelongsToMany
    {
        return $this->subscribers()->wherePivot('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('group_type', $type);
    }

    public function subscriberCount(): int
    {
        return $this->subscribers()->count();
    }

    public function assignSubscriber(EmailSubscriber $subscriber, array $context = []): void
    {
        if (! $this->subscribers()->where('email_subscribers.id', $subscriber->id)->exists()) {
            $this->subscribers()->attach($subscriber->id, [
                'assignment_source' => $context['source'] ?? 'manual',
                'is_primary' => $context['is_primary'] ?? false,
                'assigned_by' => $context['assigned_by'] ?? null,
                'assigned_at' => now(),
                'assignment_context' => json_encode($context['extra'] ?? []),
            ]);
        }
    }

    public function removeSubscriber(EmailSubscriber $subscriber): void
    {
        $this->subscribers()->detach($subscriber->id);
    }
}
