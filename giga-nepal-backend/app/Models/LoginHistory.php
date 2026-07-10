<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_status',
        'failure_reason',
        'location_data',
        'is_suspicious',
    ];

    protected $casts = [
        'location_data' => 'array',
        'is_suspicious' => 'boolean',
        'created_at' => 'datetime',
    ];

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    const FAILURE_INVALID_PASSWORD = 'invalid_password';
    const FAILURE_ACCOUNT_LOCKED = 'account_locked';
    const FAILURE_2FA_FAILED = '2fa_failed';
    const FAILURE_ACCOUNT_SUSPENDED = 'account_suspended';
    const FAILURE_INVALID_EMAIL = 'invalid_email';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get successful logins.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('login_status', self::STATUS_SUCCESS);
    }

    /**
     * Scope to get failed logins.
     */
    public function scopeFailed($query)
    {
        return $query->where('login_status', self::STATUS_FAILED);
    }

    /**
     * Scope to get suspicious logins.
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope to get recent logins.
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Mark this login as suspicious.
     */
    public function markAsSuspicious(): void
    {
        $this->update(['is_suspicious' => true]);
    }

    /**
     * Get location as formatted string.
     */
    public function getLocationStringAttribute(): ?string
    {
        if (!$this->location_data) {
            return null;
        }

        $parts = [];

        if (isset($this->location_data['city'])) {
            $parts[] = $this->location_data['city'];
        }

        if (isset($this->location_data['country'])) {
            $parts[] = $this->location_data['country'];
        }

        return implode(', ', $parts) ?: null;
    }

    /**
     * Check if the failure was due to authentication issues.
     */
    public function isAuthFailure(): bool
    {
        return in_array($this->failure_reason, [
            self::FAILURE_INVALID_PASSWORD,
            self::FAILURE_INVALID_EMAIL,
            self::FAILURE_2FA_FAILED,
        ]);
    }
}
