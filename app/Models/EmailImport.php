<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailImport extends Model
{
    use HasFactory;

    protected $table = 'email_imports';

    protected $fillable = [
        'name',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_type',
        'total_rows',
        'valid_rows',
        'imported_rows',
        'updated_rows',
        'duplicate_rows',
        'invalid_email_rows',
        'missing_email_rows',
        'suppressed_rows',
        'unsubscribed_rows',
        'failed_rows',
        'country_assigned_rows',
        'unassigned_rows',
        'status',
        'target_group_id',
        'auto_assign_by_country',
        'default_subscriber_type',
        'default_source',
        'duplicate_handling',
        'update_existing',
        'skip_unsubscribed',
        'skip_suppressed',
        'validate_dns',
        'mapping_id',
        'imported_by',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'imported_rows' => 'integer',
        'updated_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'invalid_email_rows' => 'integer',
        'missing_email_rows' => 'integer',
        'suppressed_rows' => 'integer',
        'unsubscribed_rows' => 'integer',
        'failed_rows' => 'integer',
        'country_assigned_rows' => 'integer',
        'unassigned_rows' => 'integer',
        'auto_assign_by_country' => 'boolean',
        'update_existing' => 'boolean',
        'skip_unsubscribed' => 'boolean',
        'skip_suppressed' => 'boolean',
        'validate_dns' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_VALIDATING = 'validating';
    const STATUS_IMPORTING = 'importing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    const DUPLICATE_SKIP = 'skip';
    const DUPLICATE_UPDATE = 'update';
    const DUPLICATE_MERGE = 'merge';

    public function targetGroup(): BelongsTo
    {
        return $this->belongsTo(EmailGroup::class, 'target_group_id');
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(EmailImportMapping::class, 'mapping_id');
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(EmailImportRow::class);
    }

    public function getProgressAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        return round((($this->imported_rows + $this->updated_rows + $this->duplicate_rows + $this->failed_rows) / $this->total_rows) * 100, 2);
    }

    public function getErrorReportPathAttribute(): string
    {
        return storage_path('app/email-imports/errors/' . $this->id . '_errors.csv');
    }

    public function getDuplicateReportPathAttribute(): string
    {
        return storage_path('app/email-imports/duplicates/' . $this->id . '_duplicates.csv');
    }

    public function getFinalReportPathAttribute(): string
    {
        return storage_path('app/email-imports/reports/' . $this->id . '_report.csv');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }
}
