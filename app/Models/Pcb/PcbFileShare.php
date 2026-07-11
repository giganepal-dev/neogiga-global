<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbFileShare extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'file_id',
        'shared_by_id',
        'shared_with_user_id',
        'shared_with_organization_id',
        'share_type',
        'access_token',
        'permission',
        'requires_nda',
        'nda_accepted',
        'nda_accepted_at',
        'expires_at',
        'access_count',
        'max_access_count',
    ];

    protected $casts = [
        'requires_nda' => 'boolean',
        'nda_accepted' => 'boolean',
        'nda_accepted_at' => 'datetime',
        'expires_at' => 'datetime',
        'access_count' => 'integer',
        'max_access_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($share) {
            if (empty($share->id)) {
                $share->id = (string) Str::uuid();
            }
            if (empty($share->access_token)) {
                $share->access_token = Str::random(64);
            }
        });
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_id');
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function sharedWithOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'shared_with_organization_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canAccess(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->requires_nda && !$this->nda_accepted) {
            return false;
        }

        if ($this->max_access_count && $this->access_count >= $this->max_access_count) {
            return false;
        }

        return true;
    }

    public function incrementAccess(): void
    {
        $this->increment('access_count');
    }
}
