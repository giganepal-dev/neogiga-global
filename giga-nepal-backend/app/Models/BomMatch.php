<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_import_row_id',
        'product_id',
        'confidence_score',
        'match_algorithm',
        'match_criteria',
        'is_accepted',
        'is_rejected',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected $casts = [
        'match_criteria' => 'array',
        'is_accepted' => 'boolean',
        'is_rejected' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function bomImportRow(): BelongsTo
    {
        return $this->belongsTo(BomImportRow::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
