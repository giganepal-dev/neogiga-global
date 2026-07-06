<?php

namespace App\Models\ImportExport;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends BaseModel
{
    protected $table = 'import_rows';

    protected $fillable = [
        'import_id',
        'row_number',
        'data',
        'status', // pending, success, failed
        'error_message',
        'created_record_id',
        'created_record_type',
    ];

    protected $casts = [
        'data' => 'array',
        'row_number' => 'integer',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
