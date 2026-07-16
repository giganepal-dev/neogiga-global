<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PcbFile extends Model
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
        'project_id', 'user_id', 'version_id',
        'filename_original', 'filename_stored', 'file_type', 'mime_type', 'file_size',
        'layer_type',
        'storage_disk', 'storage_path', 'encryption_key_ref',
        'malware_scanned', 'malware_clean', 'scanned_at',
        'signature_validated', 'mime_validated',
        'processing_status', 'processing_error',
        'nda_required', 'access_permissions',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'malware_scanned' => 'boolean',
        'malware_clean' => 'boolean',
        'scanned_at' => 'datetime',
        'signature_validated' => 'boolean',
        'mime_validated' => 'boolean',
        'nda_required' => 'boolean',
        'access_permissions' => 'array',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PcbProjectVersion::class, 'version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PcbFileVersion::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(PcbFileAccessLog::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PcbFileShare::class);
    }

    public function scanResults(): HasMany
    {
        return $this->hasMany(PcbFileScanResult::class);
    }

    public function gerberAnalysisRuns(): HasMany
    {
        return $this->hasMany(PcbGerberAnalysisRun::class);
    }

    public function isSecure(): bool
    {
        return $this->malware_scanned && $this->malware_clean;
    }

    public function getDownloadUrlAttribute(): ?string
    {
        // Generate signed temporary URL for private storage
        if ($this->storage_disk === 's3-private') {
            return \Storage::disk('s3')->temporaryUrl(
                $this->storage_path,
                now()->addMinutes(15)
            );
        }

        return null; // Local private storage requires controller method
    }
}
