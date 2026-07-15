<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_upload_id',
        'line_number',
        'customer_part_number',
        'mpn',
        'manufacturer',
        'description',
        'package',
        'quantity',
        'target_price',
        'currency',
        'required_date',
        'alternative_allowed',
        'reference_designator',
        'notes',
        'row_status',
        'validation_error',
        'matched_product_id',
        'match_confidence',
        'match_type',
        'match_details',
        'suggested_alternative_id',
    ];

    protected $casts = [
        'alternative_allowed' => 'boolean',
        'match_details' => 'array',
        'required_date' => 'date',
    ];

    public function bomUpload(): BelongsTo
    {
        return $this->belongsTo(BomUpload::class);
    }

    public function matchedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'matched_product_id');
    }

    public function suggestedAlternative(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'suggested_alternative_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BomMatch::class);
    }

    public function alternatives(): HasMany
    {
        return $this->hasMany(BomAlternative::class);
    }

    public function scopeMatched($query)
    {
        return $query->where('row_status', 'matched');
    }

    public function scopeUnmatched($query)
    {
        return $query->where('row_status', 'unmatched');
    }

    public function scopeInvalid($query)
    {
        return $query->where('row_status', 'invalid');
    }

    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('validation_error');
    }

    public function isMatched(): bool
    {
        return $this->row_status === 'matched' && $this->matched_product_id !== null;
    }

    public function hasAlternative(): bool
    {
        return $this->suggested_alternative_id !== null;
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->row_status, ['matched', 'valid']) && !$this->validation_error;
    }

    public function getMatchConfidencePercentageAttribute(): float
    {
        return round($this->match_confidence * 100, 2);
    }
}
