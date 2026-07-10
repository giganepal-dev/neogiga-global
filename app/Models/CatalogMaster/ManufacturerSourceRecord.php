<?php

namespace App\Models\CatalogMaster;

use App\Models\CatalogImport\CatalogSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerSourceRecord extends Model
{
    use HasFactory;

    protected $table = 'manufacturer_source_records';

    public $timestamps = true;

    protected $fillable = [
        'catalog_source_id',
        'manufacturer_id',
        'external_id',
        'record_type',
        'raw_payload',
        'parsed_data',
        'match_confidence',
        'match_status',
        'imported_at',
        'last_updated_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'parsed_data' => 'array',
        'match_confidence' => 'decimal:4',
        'imported_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    public const MATCH_STATUS_MATCHED = 'matched';
    public const MATCH_STATUS_CANDIDATE = 'candidate';
    public const MATCH_STATUS_UNMATCHED = 'unmatched';
    public const MATCH_STATUS_REJECTED = 'rejected';

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function scopeMatched($query)
    {
        return $query->where('match_status', self::MATCH_STATUS_MATCHED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('match_status', [self::MATCH_STATUS_CANDIDATE, self::MATCH_STATUS_UNMATCHED]);
    }
}
