<?php

namespace NeoGiga\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NeoGiga\Models\User;

/**
 * DeviceSession model
 * 
 * Tracks active user sessions by device for security monitoring.
 */
class DeviceSession extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'ip_address',
        'user_agent',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'device_type',
        'country',
        'city',
        'latitude',
        'longitude',
        'is_trusted',
        'last_active_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_trusted' => 'boolean',
        'last_active_at' => 'datetime',
        'expires_at' => 'datetime',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the session is still active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * Mark the session as expired/revoked.
     *
     * @return void
     */
    public function revoke(): void
    {
        $this->update([
            'expires_at' => now(),
        ]);
    }

    /**
     * Update last active timestamp.
     *
     * @return void
     */
    public function touchActivity(): void
    {
        $this->update([
            'last_active_at' => now(),
        ]);
    }

    /**
     * Scope a query to only include active sessions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include trusted sessions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Get a human-readable device description.
     *
     * @return string
     */
    public function getDeviceDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->browser) {
            $parts[] = $this->browser;
        }

        if ($this->os) {
            $parts[] = $this->os;
        }

        if ($this->device_type) {
            $parts[] = ucfirst($this->device_type);
        }

        return implode(' | ', $parts) ?: 'Unknown Device';
    }
}
