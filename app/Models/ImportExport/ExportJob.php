<?php

namespace App\Models\ImportExport;

use App\Models\BaseModel;

class ExportJob extends BaseModel
{
    protected $table = 'export_jobs';

    protected $fillable = [
        'export_type', // categories, products, vendors, orders, etc.
        'file_name',
        'file_path',
        'format', // csv, excel
        'filters',
        'total_rows',
        'status', // pending, processing, completed, failed
        'error_message',
        'requested_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'total_rows' => 'integer',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
