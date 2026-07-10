<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alert_type',
        'severity',
        'message',
        'metadata',
        'is_read',
        'read_at',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const TYPE_SUSPICIOUS_LOGIN = 'suspicious_login';
    const TYPE_BRUTE_FORCE = 'brute_force';
    const TYPE_UNUSUAL_LOCATION = 'unusual_location';
    const TYPE_NEW_DEVICE = 'new_device';
    const TYPE_ACCOUNT_LOCKED = 'account_locked';
    const TYPE_2FA_DISABLED = '2fa_disabled';
    const TYPE_PASSWORD_CHANGED = 'password_changed';
    const TYPE_SESSION_REVOKED = 'session_revoked';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read alerts.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to get unresolved alerts.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope to get resolved alerts.
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope to get critical/high severity alerts.
     */
    public function scopeCritical($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Mark alert as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark alert as resolved.
     */
    public function markAsResolved(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    /**
     * Check if alert is critical.
     */
    public function isCritical(): bool
    {
        return in_array($this->severity, [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Get alert icon based on type.
     */
    public function getIconAttribute(): string
    {
        $icons = [
            self::TYPE_SUSPICIOUS_LOGIN => '⚠️',
            self::TYPE_BRUTE_FORCE => '🚫',
            self::TYPE_UNUSUAL_LOCATION => '🌍',
            self::TYPE_NEW_DEVICE => '📱',
            self::TYPE_ACCOUNT_LOCKED => '🔒',
            self::TYPE_2FA_DISABLED => '🔓',
            self::TYPE_PASSWORD_CHANGED => '🔑',
            self::TYPE_SESSION_REVOKED => '❌',
        ];

        return $icons[$this->alert_type] ?? 'ℹ️';
    }

    /**
     * Get color based on severity.
     */
    public function getColorAttribute(): string
    {
        $colors = [
            self::SEVERITY_LOW => 'blue',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_CRITICAL => 'red',
        ];

        return $colors[$this->severity] ?? 'gray';
    }
}
