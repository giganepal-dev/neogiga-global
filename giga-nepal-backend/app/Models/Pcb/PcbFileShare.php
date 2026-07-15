<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbFileShare extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Str::uuid();
            }
        });
    }

    protected $fillable = [
        'file_id', 'shared_by_id', 'shared_with_user_id',
        'shared_with_organization_id', 'share_type',
        'expires_at', 'requires_ndas', 'nda_accepted',
        'nda_accepted_at', 'download_count', 'max_downloads',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'nda_accepted_at' => 'datetime',
        'requires_ndas' => 'boolean',
        'nda_accepted' => 'boolean',
        'download_count' => 'integer',
        'max_downloads' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'shared_by_id');
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'shared_with_user_id');
    }
}
