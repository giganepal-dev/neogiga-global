<?php

namespace NeoGiga\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NeoGiga\Models\User;

/**
 * SecurityAlert model
 * 
 * Stores security-related alerts and notifications for users.
 */
class SecurityAlert extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'security_alerts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'alert_type',
        'title',
        'message',
        'severity',
        'is_read',
        'read_at',
        'metadata',
        'ip_address',
        'device_fingerprint',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Alert type constants.
     */
    const TYPE_SUSPICIOUS_LOGIN = 'suspicious_login';
    const TYPE_NEW_DEVICE = 'new_device';
    const TYPE_PASSWORD_CHANGED = 'password_changed';
    const TYPE_2FA_ENABLED = '2fa_enabled';
    const TYPE_2FA_DISABLED = '2fa_disabled';
    const TYPE_ACCOUNT_LOCKED = 'account_locked';
    const TYPE_ACCOUNT_SUSPENDED = 'account_suspended';
    const TYPE_SESSION_REVOKED = 'session_revoked';
    const TYPE_BRUTE_FORCE = 'brute_force';
    const TYPE_DATA_EXPORT = 'data_export';

    /**
     * Severity level constants.
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the user that owns the alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unread alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to only include read alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope a query to only include alerts of a specific severity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $severity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include high/critical severity alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Scope a query to only include non-expired alerts.
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
     * Scope a query to only include alerts of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Mark the alert as read.
     *
     * @return void
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Create a new security alert.
     *
     * @param  array  $data
     * @return static
     */
    public static function createAlert(array $data): self
    {
        return static::create([
            'user_id' => $data['user_id'],
            'alert_type' => $data['alert_type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'severity' => $data['severity'] ?? self::SEVERITY_MEDIUM,
            'is_read' => false,
            'metadata' => $data['metadata'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'device_fingerprint' => $data['device_fingerprint'] ?? null,
            'expires_at' => $data['expires_at'] ?? now()->addDays(30),
        ]);
    }

    /**
     * Get the alert icon based on type.
     *
     * @return string
     */
    public function getIconAttribute(): string
    {
        return match($this->alert_type) {
            self::TYPE_SUSPICIOUS_LOGIN => '🔒',
            self::TYPE_NEW_DEVICE => '📱',
            self::TYPE_PASSWORD_CHANGED => '🔑',
            self::TYPE_2FA_ENABLED => '✅',
            self::TYPE_2FA_DISABLED => '⚠️',
            self::TYPE_ACCOUNT_LOCKED => '🔐',
            self::TYPE_ACCOUNT_SUSPENDED => '🚫',
            self::TYPE_SESSION_REVOKED => '❌',
            self::TYPE_BRUTE_FORCE => '🛡️',
            self::TYPE_DATA_EXPORT => '📤',
            default => 'ℹ️',
        };
    }

    /**
     * Get the alert color based on severity.
     *
     * @return string
     */
    public function getColorAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_LOW => 'blue',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_CRITICAL => 'red',
            default => 'gray',
        };
    }
}
