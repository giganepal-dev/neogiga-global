<?php

namespace App\Models\Pcb;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbCplLine extends Model
{
    protected $fillable = [
        'cpl_import_id', 'line_number', 'reference_designator',
        'comment', 'footprint', 'package',
        'x_mm', 'y_mm', 'rotation_deg', 'side',
        'is_dnp', 'bom_matched', 'matched_bom_line_id',
        'matched_product_id', 'placement_validated',
        'validation_errors',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'x_mm' => 'decimal:4',
        'y_mm' => 'decimal:4',
        'rotation_deg' => 'decimal:2',
        'is_dnp' => 'boolean',
        'bom_matched' => 'boolean',
        'placement_validated' => 'boolean',
        'validation_errors' => 'array',
    ];

    public function cplImport(): BelongsTo
    {
        return $this->belongsTo(PcbCplImport::class, 'cpl_import_id');
    }

    public function matchedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'matched_product_id');
    }
}
