<?php

namespace NeoGiga\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NeoGiga\Models\User;

/**
 * LoginHistory model
 * 
 * Records all login attempts for audit and security monitoring.
 */
class LoginHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'login_history';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'device_type',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'is_successful',
        'failure_reason',
        'login_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_successful' => 'boolean',
        'login_at' => 'datetime',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    /**
     * Get the user associated with the login attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include successful logins.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope a query to only include failed logins.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    /**
     * Scope a query to only include recent logins.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('login_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include logins from a specific IP.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ipAddress
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to only include suspicious logins.
     * Suspicious = failed attempts or new location/device.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspicious($query)
    {
        return $query->where(function ($q) {
            $q->where('is_successful', false)
              ->orWhereNotNull('failure_reason');
        });
    }

    /**
     * Record a login attempt.
     *
     * @param  array  $data
     * @return static
     */
    public static function recordLogin(array $data): self
    {
        return static::create([
            'user_id' => $data['user_id'] ?? null,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'] ?? null,
            'device_fingerprint' => $data['device_fingerprint'] ?? null,
            'browser' => $data['browser'] ?? null,
            'browser_version' => $data['browser_version'] ?? null,
            'os' => $data['os'] ?? null,
            'os_version' => $data['os_version'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'country' => $data['country'] ?? null,
            'region' => $data['region'] ?? null,
            'city' => $data['city'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_successful' => $data['is_successful'] ?? false,
            'failure_reason' => $data['failure_reason'] ?? null,
            'login_at' => now(),
        ]);
    }

    /**
     * Get a human-readable description of the login attempt.
     *
     * @return string
     */
    public function getDescriptionAttribute(): string
    {
        $status = $this->is_successful ? 'Successful' : 'Failed';
        $location = collect([$this->city, $this->country])
            ->filter()
            ->implode(', ');

        $device = collect([$this->browser, $this->os])
            ->filter()
            ->implode(' | ');

        $parts = [$status];

        if ($location) {
            $parts[] = "from {$location}";
        }

        if ($device) {
            $parts[] = "using {$device}";
        }

        return implode(' ', $parts);
    }
}
