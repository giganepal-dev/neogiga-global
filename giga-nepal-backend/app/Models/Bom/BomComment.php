<?php

namespace App\Models\Bom;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomComment extends Model
{
    protected $fillable = [
        'bom_import_id',
        'bom_import_line_id',
        'user_id',
        'comment',
        'comment_type',
        'is_internal',
        'metadata',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'metadata' => 'array',
    ];

    public function bomImport(): BelongsTo
    {
        return $this->belongsTo(BomImport::class);
    }

    public function bomImportLine(): BelongsTo
    {
        return $this->belongsTo(BomImportLine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope by comment type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('comment_type', $type);
    }

    /**
     * Scope to internal comments.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope to external comments (visible to all collaborators).
     */
    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }
}
