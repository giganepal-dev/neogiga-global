<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    protected $fillable = [
        'job_type',
        'status',
        'total_items',
        'processed_items',
        'created_items',
        'updated_items',
        'failed_items',
        'error_message',
        'options',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'created_items' => 'integer',
        'updated_items' => 'integer',
        'failed_items' => 'integer',
        'options' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function getProgressAttribute()
    {
        if ($this->total_items === 0) {
            return 0;
        }
        return round(($this->processed_items / $this->total_items) * 100, 2);
    }
}
