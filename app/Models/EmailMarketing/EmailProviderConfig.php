<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class EmailProviderConfig extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'provider_type',
        'scope',
        'region_id',
        'country_code',
        'group_id',
        'priority',
        'is_active',
        'is_fallback',
        'daily_quota',
        'hourly_quota',
        'per_second_rate',
        'current_daily_count',
        'current_hourly_count',
        'quota_reset_at',
        'health_status',
        'last_health_check_at',
        'credentials_encrypted',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_fallback' => 'boolean',
        'daily_quota' => 'integer',
        'hourly_quota' => 'integer',
        'per_second_rate' => 'integer',
        'current_daily_count' => 'integer',
        'current_hourly_count' => 'integer',
        'quota_reset_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'credentials_encrypted' => 'array',
        'settings' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailProviderConfig $config) {
            if (empty($config->uuid)) {
                $config->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function region()
    {
        return $this->belongsTo(\App\Models\Marketplace\Region::class);
    }

    public function group()
    {
        return $this->belongsTo(EmailGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('health_status', 'healthy');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('provider_type', $type);
    }

    public function isHealthy(): bool
    {
        return $this->health_status === 'healthy';
    }

    public function canSend(): bool
    {
        return $this->is_active && $this->isHealthy() && $this->hasQuota();
    }

    public function hasQuota(): bool
    {
        return $this->current_daily_count < $this->daily_quota
            && $this->current_hourly_count < $this->hourly_quota;
    }

    public function incrementCounts(): void
    {
        $this->increment('current_daily_count');
        $this->increment('current_hourly_count');
    }

    public function resetCounts(): void
    {
        $this->update([
            'current_daily_count' => 0,
            'current_hourly_count' => 0,
            'quota_reset_at' => now()->addHour(),
        ]);
    }
}
