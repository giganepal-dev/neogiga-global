<?php

namespace App\Models\Bom;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomCollaborator extends Model
{
    protected $fillable = [
        'bom_import_id',
        'user_id',
        'role',
        'invited_at',
        'accepted_at',
        'status',
        'permissions',
        'metadata',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'permissions' => 'array',
        'metadata' => 'array',
    ];

    public function bomImport(): BelongsTo
    {
        return $this->belongsTo(BomImport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope by role.
     */
    public function scopeOfRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to active collaborators.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Check if collaborator has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner') {
            return true;
        }

        $permissions = $this->permissions ?? [];

        return in_array('*', $permissions) || in_array($permission, $permissions);
    }

    /**
     * Get role display name.
     */
    public function getRoleDisplayAttribute(): string
    {
        return match ($this->role) {
            'owner' => 'Owner',
            'editor' => 'Editor',
            'reviewer' => 'Reviewer',
            'viewer' => 'Viewer',
            'procurement_approver' => 'Procurement Approver',
            default => ucfirst($this->role),
        };
    }
}
