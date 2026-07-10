<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'ip_address',
        'user_agent',
        'browser',
        'os',
        'device_type',
        'location_data',
        'is_active',
        'last_activity_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'location_data' => 'array',
        'last_activity_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('revoked_at');
    }

    /**
     * Revoke this session.
     */
    public function revoke(): void
    {
        $this->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);
    }

    /**
     * Update last activity timestamp.
     */
    public function touchActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Check if this is the current session.
     */
    public function isCurrentSession(string $currentFingerprint): bool
    {
        return $this->device_fingerprint === $currentFingerprint;
    }

    /**
     * Get a human-readable device description.
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

        return implode(' on ', $parts) ?: 'Unknown Device';
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
}
