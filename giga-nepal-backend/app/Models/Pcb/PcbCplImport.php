<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PcbCplImport extends Model
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
        'project_id', 'user_id', 'filename_original',
        'filename_stored', 'file_size', 'status',
        'error_message', 'total_lines', 'valid_lines', 'error_lines',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'total_lines' => 'integer',
        'valid_lines' => 'integer',
        'error_lines' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PcbCplLine::class);
    }

    public function validationErrors(): HasMany
    {
        return $this->hasMany(PcbCplValidationError::class);
    }
}
