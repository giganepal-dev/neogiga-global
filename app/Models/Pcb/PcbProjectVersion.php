<?php

namespace App\Models\Pcb;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PcbProjectVersion extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'version_number',
        'name',
        'description',
        'change_summary',
        'created_by_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($version) {
            if (empty($version->id)) {
                $version->id = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PcbFile::class);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeLatestVersion($query, $projectId)
    {
        return $query->where('project_id', $projectId)
            ->orderByDesc('version_number')
            ->limit(1);
    }

    public function isLatest(): bool
    {
        $latest = self::forProject($this->project_id)
            ->latestVersion($this->project_id)
            ->first();

        return $latest && $latest->id === $this->id;
    }
}
