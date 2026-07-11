<?php

namespace App\Models\Pcb;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PcbFile extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'version_id',
        'uploaded_by_id',
        'file_type',
        'original_filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_size',
        'checksum_sha256',
        'metadata',
        'status',
        'scan_result',
        'download_count',
        'last_downloaded_at',
        'expires_at',
        'is_encrypted',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_encrypted' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($file) {
            if (empty($file->id)) {
                $file->id = (string) Str::uuid();
            }
        });

        static::deleting(function ($file) {
            // Delete physical file when model is deleted
            if ($file->file_path && Storage::disk('private')->exists($file->file_path)) {
                Storage::disk('private')->delete($file->file_path);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PcbProjectVersion::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
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

    public function analysisRuns(): HasMany
    {
        return $this->hasMany(PcbFileAnalysisRun::class);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeFileType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    public function scopeScanned($query)
    {
        return $query->where('status', 'scanned');
    }

    public function getDownloadUrlAttribute(): string
    {
        // Generate signed URL for private file access
        return route('pcb.files.download', [
            'file' => $this->id,
            'token' => $this->generateAccessToken()
        ]);
    }

    public function generateAccessToken(int $expiryHours = 24): string
    {
        return \Illuminate\Support\Facades\Crypt::encryptString(
            "{$this->id}:{$this->uploaded_by_id}:" . now()->addHours($expiryHours)->timestamp
        );
    }

    public function isAccessibleBy(User $user): bool
    {
        // File uploader can always access
        if ($this->uploaded_by_id === $user->id) {
            return true;
        }

        // Check project access
        return $this->project->canUserAccess($user);
    }

    public function recordAccess(User $user, string $action, ?string $reason = null): void
    {
        PcbFileAccessLog::create([
            'file_id' => $this->id,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'organization_id' => $user->organization_id,
            'access_reason' => $reason,
        ]);

        if ($action === 'download') {
            $this->increment('download_count');
            $this->update(['last_downloaded_at' => now()]);
        }
    }
}
