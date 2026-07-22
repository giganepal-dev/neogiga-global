<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbFileVersion extends Model
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
        'file_id', 'version_number', 'filename_original',
        'filename_stored', 'storage_path', 'file_size',
        'change_summary', 'uploaded_by_id',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'file_size' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class, 'file_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'uploaded_by_id');
    }
}
