<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomAlternative extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_import_row_id',
        'product_id',
        'alternative_type',
        'comparison_data',
        'is_recommended',
    ];

    protected $casts = [
        'comparison_data' => 'array',
        'is_recommended' => 'boolean',
    ];

    public function bomImportRow(): BelongsTo
    {
        return $this->belongsTo(BomImportRow::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
