<?php

namespace App\Models\Pcb;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbProjectMember extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'organization_id',
        'role',
        'can_edit',
        'can_upload_files',
        'can_approve',
        'can_invite',
        'joined_at',
        'invited_by_id',
        'expires_at',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
        'can_upload_files' => 'boolean',
        'can_approve' => 'boolean',
        'can_invite' => 'boolean',
        'joined_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($member) {
            if (empty($member->id)) {
                $member->id = (string) Str::uuid();
            }
            if (empty($member->joined_at)) {
                $member->joined_at = now();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        return match ($permission) {
            'edit' => $this->can_edit || $this->role === 'owner' || $this->role === 'admin',
            'upload_files' => $this->can_upload_files || $this->role === 'owner' || $this->role === 'admin' || $this->role === 'engineer',
            'approve' => $this->can_approve || $this->role === 'owner' || $this->role === 'admin',
            'invite' => $this->can_invite || $this->role === 'owner' || $this->role === 'admin',
            default => false,
        };
    }
}
