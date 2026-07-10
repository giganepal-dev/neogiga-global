<?php

namespace App\Models\CatalogMaster;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerMergeCandidate extends Model
{
    use HasFactory;

    protected $table = 'manufacturer_merge_candidates';

    public $timestamps = true;

    protected $fillable = [
        'manufacturer_id_1',
        'manufacturer_id_2',
        'confidence_score',
        'reason',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_IGNORED = 'ignored';

    public function manufacturer1(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id_1');
    }

    public function manufacturer2(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id_2');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Get the merge reason in human-readable format
     */
    public function getReasonDescriptionAttribute(): string
    {
        $reasons = [
            'same_name' => 'Same or very similar name',
            'same_domain' => 'Same website domain',
            'same_external_id' => 'Same external ID from source',
            'alias_match' => 'Alias matches another manufacturer name',
            'manual' => 'Manually identified as duplicate',
        ];

        return $reasons[$this->reason] ?? ucfirst(str_replace('_', ' ', $this->reason));
    }
}
