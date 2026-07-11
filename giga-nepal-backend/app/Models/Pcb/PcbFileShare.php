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
        'file_id', 'shared_by_id', 'shared_with_user_id', 'shared_with_organization_id',
        'share_type', 'expires_at', 'requires_ndas', 'nda_accepted',
        'nda_accepted_at', 'download_count', 'max_downloads',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'requires_ndas' => 'boolean',
        'nda_accepted' => 'boolean',
        'nda_accepted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id ??= (string) Str::uuid());
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class, 'file_id');
    }
}
