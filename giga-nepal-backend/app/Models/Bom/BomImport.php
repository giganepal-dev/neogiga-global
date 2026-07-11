<?php

namespace App\Models\Bom;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer-uploaded bill of materials (procurement flow). Each import holds the
 * parsed lines and a rollup of how many matched the catalog. Ownership is enforced
 * at the controller by `user_id`.
 */
class BomImport extends Model
{
    protected $fillable = [
        'user_id', 'name', 'source_format', 'status', 'currency',
        'total_lines', 'matched_lines', 'unmatched_lines', 'rfq_request_id', 'meta',
    ];

    protected $casts = [
        'total_lines' => 'integer',
        'matched_lines' => 'integer',
        'unmatched_lines' => 'integer',
        'meta' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(BomImportLine::class)->orderBy('line_no');
    }
}
