<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PcbProjectVersion extends Model
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
        'project_id', 'version_number', 'change_summary',
        'created_by_id', 'snapshot_data',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'snapshot_data' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PcbFile::class, 'version_id');
    }
}
