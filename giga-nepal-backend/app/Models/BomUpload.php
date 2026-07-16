<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'session_id',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'disk',
        'path',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'matched_rows',
        'unmatched_rows',
        'duplicate_rows',
        'column_mapping',
        'parsing_errors',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'parsing_errors' => 'array',
        'processed_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($bom) {
            if (empty($bom->public_id)) {
                $bom->public_id = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BomImportRow::class);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        
        $completed = $this->matched_rows + $this->unmatched_rows + $this->invalid_rows;
        return round(($completed / $this->total_rows) * 100, 2);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['pending', 'processing', 'parsed']);
    }

    public function isReadyForSubmission(): bool
    {
        return $this->status === 'ready' && $this->valid_rows > 0;
    }
}
