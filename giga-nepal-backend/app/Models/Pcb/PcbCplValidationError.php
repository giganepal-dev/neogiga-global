<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbCplValidationError extends Model
{
    protected $fillable = [
        'cpl_import_id', 'cpl_line_id', 'line_number',
        'error_code', 'error_message', 'error_details',
        'resolved', 'resolved_by_id', 'resolved_at', 'resolution_notes',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'error_details' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function cplImport(): BelongsTo
    {
        return $this->belongsTo(PcbCplImport::class);
    }

    public function cplLine(): BelongsTo
    {
        return $this->belongsTo(PcbCplLine::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'resolved_by_id');
    }
}
