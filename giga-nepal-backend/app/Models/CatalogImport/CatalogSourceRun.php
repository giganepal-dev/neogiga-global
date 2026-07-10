<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CatalogSourceRun Model
 * 
 * Tracks each import execution from a catalog source.
 */
class CatalogSourceRun extends Model
{
    protected $fillable = [
        'catalog_source_id',
        'run_type',
        'status',
        'triggered_by',
        'triggered_by_user_id',
        'total_records',
        'processed_records',
        'success_records',
        'error_records',
        'skipped_records',
        'staged_records',
        'published_records',
        'filters_applied',
        'error_summary',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'filters_applied' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'success_records' => 'integer',
        'error_records' => 'integer',
        'skipped_records' => 'integer',
        'staged_records' => 'integer',
        'published_records' => 'integer',
        'duration_seconds' => 'float',
    ];

    /**
     * Run type constants
     */
    const RUN_TYPE_FULL = 'full';
    const RUN_TYPE_INCREMENTAL = 'incremental';
    const RUN_TYPE_REFRESH = 'refresh';
    const RUN_TYPE_ON_DEMAND = 'on_demand';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PARTIAL = 'partial';

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }

    public function triggerUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'triggered_by_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CatalogSourceRunLog::class, 'catalog_source_run_id');
    }

    /**
     * Start the run
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark run as completed
     */
    public function complete(): void
    {
        $this->update([
            'status' => $this->error_records > 0 ? self::STATUS_PARTIAL : self::STATUS_COMPLETED,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
        ]);
    }

    /**
     * Mark run as failed
     */
    public function fail(string $errorSummary): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_summary' => $errorSummary,
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
        ]);
    }

    /**
     * Increment processed count
     */
    public function incrementProcessed(int $count = 1): void
    {
        $this->increment('processed_records', $count);
    }

    /**
     * Record a successful record processing
     */
    public function recordSuccess(): void
    {
        $this->increment('success_records');
        $this->increment('processed_records');
    }

    /**
     * Record an error
     */
    public function recordError(): void
    {
        $this->increment('error_records');
        $this->increment('processed_records');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_records === 0) {
            return 0.0;
        }
        return round(($this->success_records / $this->processed_records) * 100, 2);
    }

    /**
     * Scope to get running runs
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope to get failed runs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get recent runs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
