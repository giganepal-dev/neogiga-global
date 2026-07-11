<?php

namespace App\Models\Bom;

use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an uploaded BOM. `match_status` is exact|multiple|manual|none and
 * `match_confidence` is 0-100. When ambiguous, `candidates` lists the products a
 * reviewer can choose between.
 */
class BomImportLine extends Model
{
    protected $fillable = [
        'bom_import_id', 'line_no', 'raw_reference', 'mpn', 'manufacturer', 'description',
        'quantity', 'matched_product_id', 'match_status', 'match_confidence', 'candidates', 'notes',
    ];

    protected $casts = [
        'line_no' => 'integer',
        'quantity' => 'decimal:3',
        'match_confidence' => 'integer',
        'candidates' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(BomImport::class, 'bom_import_id');
    }

    public function matchedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'matched_product_id');
    }

    public function isMatched(): bool
    {
        return $this->matched_product_id !== null;
    }
}
