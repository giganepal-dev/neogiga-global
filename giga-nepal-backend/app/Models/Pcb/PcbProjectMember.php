<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbProjectMember extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'role',
        'access_expires_at', 'nda_accepted', 'nda_accepted_at',
    ];

    protected $casts = [
        'access_expires_at' => 'datetime',
        'nda_accepted' => 'boolean',
        'nda_accepted_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function hasExpired(): bool
    {
        return $this->access_expires_at && $this->access_expires_at->isPast();
    }

    public function canAccess(): bool
    {
        return !$this->hasExpired() && (!$this->nda_required || $this->nda_accepted);
    }
}
