<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VendorMemberInvitation extends Model
{
    protected $table = 'vendor_member_invitations';

    protected $fillable = [
        'vendor_id',
        'email',
        'role',
        'token',
        'is_accepted',
        'is_expired',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'invited_by',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
        'is_expired' => 'boolean',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'accepted_by');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'invited_by');
    }

    public static function createInvitation(int $vendorId, string $email, string $role, int $invitedBy): self
    {
        return static::create([
            'vendor_id' => $vendorId,
            'email' => strtolower(trim($email)),
            'role' => $role,
            'token' => Str::random(64),
            'is_accepted' => false,
            'is_expired' => false,
            'expires_at' => now()->addDays(7),
            'invited_by' => $invitedBy,
        ]);
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopePending($query)
    {
        return $query->where('is_accepted', false)->where('is_expired', false);
    }

    public function isExpired(): bool
    {
        return $this->is_expired || $this->expires_at->isPast();
    }

    public function accept(int $userId): bool
    {
        if ($this->isAccepted() || $this->isExpired()) {
            return false;
        }

        $this->update([
            'is_accepted' => true,
            'accepted_at' => now(),
            'accepted_by' => $userId,
        ]);

        return true;
    }

    public function isAccepted(): bool
    {
        return $this->is_accepted;
    }

    public function expire(): void
    {
        $this->update(['is_expired' => true]);
    }

    public function getAcceptanceUrlAttribute(): string
    {
        return url("/seller/invite/{$this->token}");
    }
}
