<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailImportRow extends Model
{
    use HasFactory;

    protected $table = 'email_import_rows';

    protected $fillable = [
        'import_id',
        'row_number',
        'raw_data',
        'mapped_data',
        'email',
        'normalized_email',
        'country_code',
        'status',
        'validation_errors',
        'subscriber_id',
        'action_taken',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'raw_data' => 'array',
        'mapped_data' => 'array',
        'validation_errors' => 'array',
        'processed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_VALID = 'valid';
    const STATUS_INVALID = 'invalid';
    const STATUS_IMPORTED = 'imported';
    const STATUS_UPDATED = 'updated';
    const STATUS_DUPLICATE = 'duplicate';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_FAILED = 'failed';

    const ACTION_NONE = 'none';
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_MERGED = 'merged';
    const ACTION_SKIPPED = 'skipped';
    const ACTION_FAILED = 'failed';

    public function import(): BelongsTo
    {
        return $this->belongsTo(EmailImport::class, 'import_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(EmailSubscriber::class, 'subscriber_id');
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID || 
               $this->status === self::STATUS_IMPORTED || 
               $this->status === self::STATUS_UPDATED;
    }

    public function hasErrors(): bool
    {
        return !empty($this->validation_errors) || !empty($this->error_message);
    }

    public function addValidationError(string $error): void
    {
        $errors = $this->validation_errors ?? [];
        $errors[] = $error;
        $this->validation_errors = array_unique($errors);
        $this->save();
    }

    public function markAsValid(): void
    {
        $this->update(['status' => self::STATUS_VALID]);
    }

    public function markAsInvalid(array $errors = []): void
    {
        $this->update([
            'status' => self::STATUS_INVALID,
            'validation_errors' => $errors,
        ]);
    }

    public function markAsImported(int $subscriberId): void
    {
        $this->update([
            'status' => self::STATUS_IMPORTED,
            'subscriber_id' => $subscriberId,
            'action_taken' => self::ACTION_CREATED,
            'processed_at' => now(),
        ]);
    }

    public function markAsUpdated(int $subscriberId): void
    {
        $this->update([
            'status' => self::STATUS_UPDATED,
            'subscriber_id' => $subscriberId,
            'action_taken' => self::ACTION_UPDATED,
            'processed_at' => now(),
        ]);
    }

    public function markAsDuplicate(): void
    {
        $this->update([
            'status' => self::STATUS_DUPLICATE,
            'action_taken' => self::ACTION_SKIPPED,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'action_taken' => self::ACTION_FAILED,
        ]);
    }
}
