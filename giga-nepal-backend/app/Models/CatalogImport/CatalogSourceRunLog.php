<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogSourceRunLog Model
 * 
 * Detailed logs for catalog source import runs.
 */
class CatalogSourceRunLog extends Model
{
    protected $fillable = [
        'catalog_source_run_id',
        'level',
        'stage',
        'record_number',
        'external_id',
        'message',
        'context',
        'stack_trace',
    ];

    protected $casts = [
        'context' => 'array',
        'record_number' => 'integer',
    ];

    /**
     * Log level constants
     */
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Stage constants
     */
    const STAGE_FETCH = 'fetch';
    const STAGE_PARSE = 'parse';
    const STAGE_VALIDATE = 'validate';
    const STAGE_STAGE = 'stage';
    const STAGE_TRANSFORM = 'transform';
    const STAGE_PUBLISH = 'publish';

    public function run(): BelongsTo
    {
        return $this->belongsTo(CatalogSourceRun::class, 'catalog_source_run_id');
    }

    /**
     * Scope to get only errors
     */
    public function scopeErrors($query)
    {
        return $query->whereIn('level', [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
    }

    /**
     * Scope to get only warnings
     */
    public function scopeWarnings($query)
    {
        return $query->where('level', self::LEVEL_WARNING);
    }

    /**
     * Scope to filter by stage
     */
    public function scopeForStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    /**
     * Create an error log entry
     */
    public static function logError(
        int $runId,
        string $message,
        ?string $stage = null,
        ?int $recordNumber = null,
        ?string $externalId = null,
        array $context = [],
        ?string $stackTrace = null
    ): self {
        return static::create([
            'catalog_source_run_id' => $runId,
            'level' => self::LEVEL_ERROR,
            'stage' => $stage,
            'record_number' => $recordNumber,
            'external_id' => $externalId,
            'message' => $message,
            'context' => $context,
            'stack_trace' => $stackTrace,
        ]);
    }

    /**
     * Create an info log entry
     */
    public static function logInfo(
        int $runId,
        string $message,
        ?string $stage = null,
        array $context = []
    ): self {
        return static::create([
            'catalog_source_run_id' => $runId,
            'level' => self::LEVEL_INFO,
            'stage' => $stage,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
