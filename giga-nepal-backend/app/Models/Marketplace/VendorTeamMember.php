<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorTeamMember extends Model
{
    protected $table = 'vendor_team_members';

    protected $fillable = [
        'vendor_id',
        'user_id',
        'role',
        'permissions',
        'is_active',
        'last_active_at',
        'invited_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_CATALOG_MANAGER = 'catalog_manager';
    const ROLE_INVENTORY_MANAGER = 'inventory_manager';
    const ROLE_ORDER_MANAGER = 'order_manager';
    const ROLE_LOGISTICS_MANAGER = 'logistics_manager';
    const ROLE_FINANCE_MANAGER = 'finance_manager';
    const ROLE_SUPPORT_AGENT = 'support_agent';
    const ROLE_VIEWER = 'viewer';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'invited_by');
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin()) {
            return true;
        }

        if (is_array($this->permissions)) {
            return in_array($permission, $this->permissions);
        }

        return false;
    }

    public function updateLastActive(): void
    {
        $this->update(['last_active_at' => now()]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }
}
