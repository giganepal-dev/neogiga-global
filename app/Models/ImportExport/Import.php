<?php

namespace App\Models\ImportExport;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends BaseModel
{
    protected $table = 'imports';

    protected $fillable = [
        'import_type', // categories, products, vendors, etc.
        'file_name',
        'file_path',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'status', // pending, processing, completed, failed
        'is_dry_run',
        'error_message',
        'processed_by',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'is_dry_run' => 'boolean',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
